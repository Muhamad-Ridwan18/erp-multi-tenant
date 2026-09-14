<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.products.view'), 403);

        $products = Product::query()->orderBy('name')->get();

        return view('tenant.products.index', compact('products'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('inventory.products.create'), 403);

        return view('tenant.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.products.create'), 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique(Product::class, 'sku')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'integer', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Product::query()->create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('tenant.products.index')
            ->with('status', 'Product created.');
    }

    public function edit(Request $request, Product $product): View
    {
        abort_unless($request->user()->can('inventory.products.update'), 403);

        $product->load(['stockMovements' => fn ($q) => $q->latest()->limit(20)]);

        return view('tenant.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.products.update'), 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique(Product::class, 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('tenant.products.index')
            ->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.products.delete'), 403);

        if ($product->stockMovements()->exists()) {
            return back()->withErrors(['product' => 'Product has stock history and cannot be deleted. Deactivate it instead.']);
        }

        $product->delete();

        return redirect()
            ->route('tenant.products.index')
            ->with('status', 'Product deleted.');
    }

    public function adjust(Request $request, Product $product, StockService $stock): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.stock.adjust'), 403);

        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $stock->adjust($product, (int) $data['delta'], $request->user(), $data['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delta' => $e->getMessage()]);
        }

        return back()->with('status', 'Stock adjusted.');
    }
}
