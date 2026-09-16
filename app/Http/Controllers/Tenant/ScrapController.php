<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockOperation;
use App\Services\InventoryOperationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ScrapController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.scraps.view'), 403);

        $scraps = StockOperation::query()
            ->where('type', 'scrap')
            ->with(['moves.product', 'sourceLocation'])
            ->latest()
            ->get();

        return view('tenant.scraps.index', compact('scraps'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('inventory.scraps.create'), 403);

        $products = Product::query()->where('is_active', true)->where('type', 'goods')->orderBy('name')->get();
        $locations = Location::query()->where('type', 'internal')->where('is_active', true)->orderBy('name')->get();

        return view('tenant.scraps.create', compact('products', 'locations'));
    }

    public function store(Request $request, InventoryOperationService $operations): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.scraps.create'), 403);

        $data = $request->validate([
            'location_id' => ['nullable', Rule::exists(Location::class, 'id')],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $source = ! empty($data['location_id'])
            ? Location::query()->findOrFail($data['location_id'])
            : null;

        try {
            $operation = $operations->createScrap(
                lines: collect($data['items'])->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ])->all(),
                user: $request->user(),
                source: $source,
                notes: $data['notes'] ?? null,
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.operations.show', $operation)
            ->with('status', 'Scrap validated and stock updated.');
    }
}
