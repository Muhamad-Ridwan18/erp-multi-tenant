<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('sales.orders.view'), 403);

        $orders = SalesOrder::query()
            ->with('customer')
            ->latest()
            ->get();

        return view('tenant.orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('sales.orders.create'), 403);

        $customers = Customer::query()->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();

        return view('tenant.orders.create', compact('customers', 'products'));
    }

    public function store(Request $request, SalesOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('sales.orders.create'), 403);

        $data = $request->validate([
            'customer_id' => ['required', Rule::exists(Customer::class, 'id')],
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

                $order = SalesOrder::query()->create([
                    'number' => $orders->nextNumber(),
                    'customer_id' => $data['customer_id'],
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
            ->route('tenant.orders.show', $order)
            ->with('status', 'Sales order created as draft.');
    }

    public function show(Request $request, SalesOrder $order): View
    {
        abort_unless($request->user()->can('sales.orders.view'), 403);

        $order->load(['customer', 'items.product', 'creator', 'invoice']);

        return view('tenant.orders.show', compact('order'));
    }

    public function confirm(Request $request, SalesOrder $order, SalesOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('sales.orders.confirm'), 403);

        try {
            $orders->confirm($order, $request->user());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Order confirmed and stock deducted.');
    }

    public function destroy(Request $request, SalesOrder $order): RedirectResponse
    {
        abort_unless($request->user()->can('sales.orders.delete'), 403);
        abort_unless($order->isDraft(), 403, 'Only draft orders can be deleted.');

        $order->items()->delete();
        $order->delete();

        return redirect()
            ->route('tenant.orders.index')
            ->with('status', 'Draft order deleted.');
    }
}
