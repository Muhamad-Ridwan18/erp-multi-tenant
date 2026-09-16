<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Lot;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\UnbuildOrder;
use App\Services\ManufacturingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class UnbuildOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.unbuilds.view'), 403);

        $orders = UnbuildOrder::query()->with(['product', 'billOfMaterial'])->latest()->get();

        return view('tenant.unbuilds.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.unbuilds.create'), 403);

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', BillOfMaterial::query()->where('is_active', true)->select('product_id'))
            ->orderBy('name')
            ->get();
        $boms = BillOfMaterial::query()->with('product')->where('is_active', true)->latest()->get();
        $lots = Lot::query()->with('product')->latest()->limit(100)->get();

        return view('tenant.unbuilds.create', compact('products', 'boms', 'lots'));
    }

    public function store(Request $request, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.unbuilds.create'), 403);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'bill_of_material_id' => ['nullable', Rule::exists(BillOfMaterial::class, 'id')],
            'manufacturing_order_id' => ['nullable', Rule::exists(ManufacturingOrder::class, 'id')],
            'lot_id' => ['nullable', Rule::exists(Lot::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $order = $manufacturing->createUnbuild($data, $request->user());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['product_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.unbuilds.show', $order)
            ->with('status', 'Unbuild order created as draft.');
    }

    public function show(Request $request, UnbuildOrder $unbuild): View
    {
        abort_unless($request->user()->can('manufacturing.unbuilds.view'), 403);

        $unbuild->load(['product', 'billOfMaterial.lines.product', 'lot', 'manufacturingOrder']);

        return view('tenant.unbuilds.show', ['order' => $unbuild]);
    }

    public function validateOrder(Request $request, UnbuildOrder $unbuild, ManufacturingService $manufacturing): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.unbuilds.validate'), 403);

        try {
            $manufacturing->validateUnbuild($unbuild, $request->user());
        } catch (InvalidArgumentException|Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Unbuild validated; components returned to stock.');
    }
}
