<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Uom;
use App\Services\StockService;
use App\Support\TenantMasterData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

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

        $categories = TenantMasterData::productCategories();
        $uoms = TenantMasterData::uoms();

        return view('tenant.products.create', compact('categories', 'uoms'));
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.products.create'), 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique(Product::class, 'sku')],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(['goods', 'service'])],
            'barcode' => ['nullable', 'string', 'max:100'],
            'product_category_id' => ['nullable', Rule::exists(ProductCategory::class, 'id')],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:20'],
            'uom_id' => ['nullable', Rule::exists(Uom::class, 'id')],
            'purchase_uom_id' => ['nullable', Rule::exists(Uom::class, 'id')],
            'price' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $openingStock = (int) $data['stock_qty'];

        $product = Product::query()->create([
            ...$data,
            'type' => $data['type'] ?? 'goods',
            'unit' => ($data['unit'] ?? null) ?: 'pcs',
            'cost' => $data['cost'] ?? 0,
            'stock_qty' => 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($openingStock > 0) {
            $this->applyOpeningStock($product, $openingStock, $request, $stock);
        }

        return redirect()
            ->route('tenant.products.index')
            ->with('status', 'Product created.');
    }

    public function edit(Request $request, Product $product): View
    {
        abort_unless($request->user()->can('inventory.products.update'), 403);

        $product->load(['stockMovements' => fn ($q) => $q->latest()->limit(20)]);
        $categories = TenantMasterData::productCategories();
        $uoms = TenantMasterData::uoms();

        return view('tenant.products.edit', compact('product', 'categories', 'uoms'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.products.update'), 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique(Product::class, 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(['goods', 'service'])],
            'barcode' => ['nullable', 'string', 'max:100'],
            'product_category_id' => ['nullable', Rule::exists(ProductCategory::class, 'id')],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:20'],
            'uom_id' => ['nullable', Rule::exists(Uom::class, 'id')],
            'purchase_uom_id' => ['nullable', Rule::exists(Uom::class, 'id')],
            'price' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            ...$data,
            'type' => $data['type'] ?? $product->type ?? 'goods',
            'unit' => ($data['unit'] ?? null) ?: ($product->unit ?: 'pcs'),
            'cost' => $data['cost'] ?? 0,
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

    /**
     * Put opening stock on the default internal location. Tenants without an
     * inventory location set up fall back to the cached quantity on the product.
     */
    protected function applyOpeningStock(Product $product, int $quantity, Request $request, StockService $stock): void
    {
        try {
            $stock->adjust($product, $quantity, $request->user(), 'Opening stock');
        } catch (Throwable) {
            $product->update(['stock_qty' => $quantity]);
        }
    }
}
