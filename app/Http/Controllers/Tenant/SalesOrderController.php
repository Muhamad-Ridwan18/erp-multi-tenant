<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use App\Support\DocumentLineCalculator;
use App\Support\TenantMasterData;
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
        $paymentTerms = TenantMasterData::paymentTerms();
        $warehouses = TenantMasterData::warehouses();
        $taxes = TenantMasterData::taxes();
        $uoms = TenantMasterData::uoms();

        return view('tenant.orders.create', compact('customers', 'products', 'paymentTerms', 'warehouses', 'taxes', 'uoms'));
    }

    public function store(Request $request, SalesOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('sales.orders.create'), 403);

        $data = $request->validate([
            'customer_id' => ['required', Rule::exists(Customer::class, 'id')],
            'kind' => ['nullable', Rule::in(['quotation', 'order'])],
            'payment_term_id' => ['nullable', Rule::exists(PaymentTerm::class, 'id')],
            'warehouse_id' => ['nullable', Rule::exists(Warehouse::class, 'id')],
            'validity_date' => ['nullable', 'date'],
            'ordered_at' => ['nullable', 'date'],
            'commitment_date' => ['nullable', 'date'],
            'client_order_ref' => ['nullable', 'string', 'max:100'],
            'salesperson_id' => ['nullable', Rule::exists(User::class, 'id')],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $kind = $data['kind'] ?? 'order';

        try {
            $order = DB::connection('tenant')->transaction(function () use ($data, $kind, $request, $orders) {
                $summary = DocumentLineCalculator::summarize($data['items']);

                $order = SalesOrder::query()->create([
                    'number' => $orders->nextNumber($kind),
                    'kind' => $kind,
                    'customer_id' => $data['customer_id'],
                    'payment_term_id' => $data['payment_term_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'validity_date' => $data['validity_date'] ?? null,
                    'ordered_at' => $data['ordered_at'] ?? null,
                    'commitment_date' => $data['commitment_date'] ?? null,
                    'client_order_ref' => $data['client_order_ref'] ?? null,
                    'salesperson_id' => $data['salesperson_id'] ?? $request->user()->id,
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
            ->route('tenant.orders.show', $order)
            ->with('status', $kind === 'quotation' ? 'Quotation created as draft.' : 'Sales order created as draft.');
    }

    public function show(Request $request, SalesOrder $order): View
    {
        abort_unless($request->user()->can('sales.orders.view'), 403);

        $order->load(['customer', 'items.product', 'creator', 'invoice', 'deliveries.moves.product']);

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

        return back()->with('status', 'Order confirmed and ready for delivery.');
    }

    public function deliver(Request $request, SalesOrder $order, SalesOrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.operations.create'), 403);

        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', Rule::exists(Product::class, 'id')],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:0'],
        ]);

        $lines = null;

        if (array_key_exists('items', $data)) {
            $lines = array_values(array_filter(
                array_map(fn (array $line) => [
                    'product_id' => (int) $line['product_id'],
                    'quantity' => (int) $line['quantity'],
                ], $data['items'] ?? []),
                fn (array $line) => $line['quantity'] > 0,
            ));

            if ($lines === []) {
                return back()->withErrors(['items' => 'Enter at least one quantity to deliver.']);
            }
        }

        try {
            $orders->deliver($order, $request->user(), $lines);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Delivery validated and stock deducted.');
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
