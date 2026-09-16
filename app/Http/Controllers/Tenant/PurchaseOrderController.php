<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Location;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use App\Support\DocumentLineCalculator;
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
            'partner_reference' => ['nullable', 'string', 'max:100'],
            'payment_term_id' => ['nullable', Rule::exists(PaymentTerm::class, 'id')],
            'currency_id' => ['nullable', Rule::exists(Currency::class, 'id')],
            'destination_location_id' => ['nullable', Rule::exists(Location::class, 'id')],
            'ordered_at' => ['nullable', 'date'],
            'planned_at' => ['nullable', 'date'],
            'origin' => ['nullable', 'string', 'max:100'],
            'buyer_id' => ['nullable', Rule::exists(User::class, 'id')],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $order = DB::connection('tenant')->transaction(function () use ($data, $request, $orders) {
                $summary = DocumentLineCalculator::summarize($data['items']);

                $order = PurchaseOrder::query()->create([
                    'number' => $orders->nextNumber(),
                    'vendor_id' => $data['vendor_id'],
                    'partner_reference' => $data['partner_reference'] ?? null,
                    'payment_term_id' => $data['payment_term_id'] ?? null,
                    'currency_id' => $data['currency_id'] ?? null,
                    'destination_location_id' => $data['destination_location_id'] ?? null,
                    'ordered_at' => $data['ordered_at'] ?? null,
                    'planned_at' => $data['planned_at'] ?? null,
                    'origin' => $data['origin'] ?? null,
                    'buyer_id' => $data['buyer_id'] ?? $request->user()->id,
                    'status' => 'draft',
                    'subtotal' => $summary['subtotal'],
                    'discount_total' => $summary['discount_total'],
                    'tax_total' => $summary['tax_total'],
                    'grand_total' => $summary['grand_total'],
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($summary['lines'] as $line) {
                    $order->items()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'discount_percent' => $line['discount_percent'],
                        'tax_percent' => $line['tax_percent'],
                        'line_total' => $line['line_total'],
                    ]);
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

        $purchase->load(['vendor', 'items.product', 'creator', 'bill', 'stockOperations.moves.product']);

        return view('tenant.purchases.show', ['order' => $purchase]);
    }

    public function send(Request $request, PurchaseOrder $purchase, PurchaseOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.orders.update'), 403);

        try {
            $orders->send($purchase);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'RFQ sent to vendor.');
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

        $data = $request->validate([
            'receive_items' => ['nullable', 'array'],
        ]);

        $lines = null;

        if (array_key_exists('receive_items', $data)) {
            $lines = $this->receiptLines($data['receive_items'] ?? []);

            if ($lines === []) {
                return back()->withErrors(['receive_items' => 'Enter at least one quantity to receive.']);
            }
        }

        try {
            $orders->receive($purchase, $request->user(), $lines);
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

    /**
     * Accepts either `receive_items[product_id] = qty` from the receipt form or
     * a list of `{product_id, quantity}` rows.
     *
     * @param  array<int|string, mixed>  $input
     * @return array<int, array{product_id: int, quantity: int}>
     */
    protected function receiptLines(array $input): array
    {
        $lines = [];

        foreach ($input as $key => $value) {
            $productId = is_array($value) ? (int) ($value['product_id'] ?? $key) : (int) $key;
            $quantity = is_array($value) ? (int) ($value['quantity'] ?? 0) : (int) $value;

            if ($productId > 0 && $quantity > 0) {
                $lines[] = ['product_id' => $productId, 'quantity' => $quantity];
            }
        }

        return $lines;
    }
}
