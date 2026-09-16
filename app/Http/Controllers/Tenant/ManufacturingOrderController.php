<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\WorkCenter;
use App\Models\WorkOrder;
use App\Services\ManufacturingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class ManufacturingOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.orders.view'), 403);

        $orders = ManufacturingOrder::query()
            ->with(['product', 'workCenter'])
            ->latest()
            ->get();

        return view('tenant.manufacturing-orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.orders.create'), 403);

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', BillOfMaterial::query()->where('is_active', true)->select('product_id'))
            ->orderBy('name')
            ->get();
        $boms = BillOfMaterial::query()->with('product')->where('is_active', true)->latest()->get();
        $workCenters = WorkCenter::query()->where('is_active', true)->orderBy('name')->get();

        return view('tenant.manufacturing-orders.create', compact('products', 'boms', 'workCenters'));
    }

    public function store(Request $request, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.orders.create'), 403);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'bill_of_material_id' => ['nullable', Rule::exists(BillOfMaterial::class, 'id')],
            'work_center_id' => ['nullable', Rule::exists(WorkCenter::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'origin' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        try {
            $order = $manufacturing->create($data, $request->user());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['product_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.manufacturing-orders.show', $order)
            ->with('status', 'Manufacturing order created as draft.');
    }

    public function show(Request $request, ManufacturingOrder $manufacturingOrder): View
    {
        abort_unless($request->user()->can('manufacturing.orders.view'), 403);

        $manufacturingOrder->load([
            'product',
            'billOfMaterial',
            'workCenter',
            'components.product',
            'operations',
            'workOrders.workCenter',
            'creator',
        ]);

        return view('tenant.manufacturing-orders.show', ['order' => $manufacturingOrder]);
    }

    public function confirm(Request $request, ManufacturingOrder $manufacturingOrder, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.orders.confirm'), 403);

        try {
            $manufacturing->confirm($manufacturingOrder);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Manufacturing order confirmed.');
    }

    public function produce(Request $request, ManufacturingOrder $manufacturingOrder, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.orders.produce'), 403);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
            'lot_name' => ['nullable', 'string', 'max:100'],
            'lot_id' => ['nullable', 'integer'],
        ]);

        try {
            $manufacturing->produce(
                $manufacturingOrder,
                $request->user(),
                $data['quantity'] ?? null,
                [
                    'lot_name' => $data['lot_name'] ?? null,
                    'lot_id' => $data['lot_id'] ?? null,
                ]
            );
        } catch (Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Production completed.');
    }

    public function completeWorkOrder(Request $request, ManufacturingOrder $manufacturingOrder, WorkOrder $workOrder, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.orders.produce'), 403);
        abort_unless((int) $workOrder->manufacturing_order_id === (int) $manufacturingOrder->id, 404);

        try {
            $manufacturing->completeWorkOrder($workOrder);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', "Work order {$workOrder->name} completed.");
    }

    public function cancel(Request $request, ManufacturingOrder $manufacturingOrder, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.orders.update'), 403);

        try {
            $manufacturing->cancel($manufacturingOrder);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Manufacturing order canceled.');
    }
}
