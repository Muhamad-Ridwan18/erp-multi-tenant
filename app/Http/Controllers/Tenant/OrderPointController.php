<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Location;
use App\Models\OrderPoint;
use App\Models\Product;
use App\Services\ManufacturingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class OrderPointController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.order_points.view'), 403);

        $orderPoints = OrderPoint::query()
            ->with(['product', 'location'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('tenant.order-points.index', compact('orderPoints'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.order_points.manage'), 403);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'location_id' => ['nullable', Rule::exists(Location::class, 'id')],
            'min_qty' => ['required', 'integer', 'min:0'],
            'max_qty' => ['nullable', 'integer', 'min:0'],
        ]);

        OrderPoint::query()->updateOrCreate(
            [
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'] ?? null,
            ],
            [
                'min_qty' => $data['min_qty'],
                'max_qty' => $data['max_qty'] ?? $data['min_qty'],
                'trigger' => 'auto',
                'is_active' => true,
            ]
        );

        return back()->with('status', 'Replenishment rule saved.');
    }

    public function replenish(Request $request, OrderPoint $orderPoint, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.order_points.manage'), 403);

        $qty = $orderPoint->suggestedQty();
        if ($qty <= 0) {
            return back()->withErrors(['order_point' => 'Stock is already at or above the target.']);
        }

        $bom = BillOfMaterial::query()
            ->where('product_id', $orderPoint->product_id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $bom) {
            return back()->withErrors(['order_point' => 'No BOM for this product. Create a purchase order manually.']);
        }

        try {
            $order = $manufacturing->create([
                'product_id' => $orderPoint->product_id,
                'bill_of_material_id' => $bom->id,
                'quantity' => $qty,
                'origin' => 'Replenish OP#'.$orderPoint->id,
            ], $request->user());
            $manufacturing->confirm($order);
        } catch (Throwable $e) {
            return back()->withErrors(['order_point' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.manufacturing-orders.show', $order)
            ->with('status', "Manufacturing order {$order->number} created for replenishment.");
    }
}
