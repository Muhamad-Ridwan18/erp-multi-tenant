<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryOperationService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BarcodeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.barcode.scan'), 403);

        return view('tenant.barcode.index');
    }

    public function lookup(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('inventory.barcode.scan'), 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'message' => 'Empty barcode.'], 422);
        }

        $product = Product::query()
            ->where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (! $product) {
            return response()->json(['found' => false, 'message' => 'No product for this barcode.']);
        }

        return response()->json([
            'found' => true,
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'stock_qty' => $product->stock_qty,
                'price' => $product->price,
            ],
        ]);
    }

    public function adjust(Request $request, StockService $stock): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.barcode.scan'), 403);
        abort_unless($request->user()->can('inventory.stock.adjust'), 403);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'delta' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);

        try {
            $stock->adjust($product, (int) $data['delta'], $request->user(), $data['notes'] ?? 'Barcode adjust');
        } catch (Throwable $e) {
            return back()->withErrors(['barcode' => $e->getMessage()]);
        }

        return back()->with('status', "Adjusted {$product->name} by {$data['delta']}.");
    }

    public function scrap(Request $request, InventoryOperationService $operations): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.barcode.scan'), 403);
        abort_unless($request->user()->can('inventory.scraps.create'), 403);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $operations->createScrap(
                lines: [['product_id' => (int) $data['product_id'], 'quantity' => (int) $data['quantity']]],
                user: $request->user(),
                notes: $data['notes'] ?? 'Barcode scrap',
            );
        } catch (Throwable $e) {
            return back()->withErrors(['barcode' => $e->getMessage()]);
        }

        return back()->with('status', 'Scrap recorded from barcode scan.');
    }
}
