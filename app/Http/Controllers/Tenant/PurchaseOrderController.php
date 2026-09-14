<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('procurement.orders.view'), 403);

        $orders = PurchaseOrder::query()
            ->with('vendor')
            ->latest()
            ->get();

        return view('tenant.purchases.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('procurement.orders.create'), 403);

        $vendors = Vendor::query()->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();

        return view('tenant.purchases.create', compact('vendors', 'products'));
    }

    public function store(Request $request, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.orders.create'), 403);

        $data = $request->validate([
            'vendor_id' => ['required', Rule::exists(Vendor::class, 'id')],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $order = DB::connection('tenant')->transaction(function () use ($data, $request, $orders) {
                $subtotal = 0;
                $linePayload = [];

                foreach ($data['items'] as $row) {
                    $qty = (int) $row['quantity'];
                    $unitPrice = (int) $row['unit_price'];
                    $lineTotal = $unitPrice * $qty;
                    $subtotal += $lineTotal;
                    $linePayload[] = [
                        'product_id' => $row['product_id'],
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                $order = PurchaseOrder::query()->create([
                    'number' => $orders->nextNumber(),
                    'vendor_id' => $data['vendor_id'],
                    'status' => 'draft',
                    'subtotal' => $subtotal,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($linePayload as $line) {
                    $order->items()->create($line);
                }

                return $order;
            });
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.purchases.show', $order)
            ->with('status', 'Purchase order created as draft.');
    }

    public function show(Request $request, PurchaseOrder $purchase): View
    {
        abort_unless($request->user()->can('procurement.orders.view'), 403);

        $purchase->load(['vendor', 'items.product', 'creator', 'bill']);

        return view('tenant.purchases.show', ['order' => $purchase]);
    }

    public function confirm(Request $request, PurchaseOrder $purchase, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.orders.confirm'), 403);

        try {
            $orders->confirm($purchase);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Purchase order confirmed.');
    }

    public function receive(Request $request, PurchaseOrder $purchase, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.receipts.receive'), 403);

        try {
            $orders->receive($purchase, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Goods received and stock updated.');
    }

    public function destroy(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.orders.delete'), 403);
        abort_unless($purchase->isDraft(), 403, 'Only draft purchase orders can be deleted.');

        $purchase->items()->delete();
        $purchase->delete();

        return redirect()
            ->route('tenant.purchases.index')
            ->with('status', 'Draft purchase order deleted.');
    }
}
