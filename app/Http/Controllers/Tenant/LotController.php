<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LotController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.lots.view'), 403);

        $lots = Lot::query()
            ->with('product')
            ->latest()
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('tracking', ['lot', 'serial'])
            ->orderBy('name')
            ->get();

        return view('tenant.lots.index', compact('lots', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.lots.manage'), 403);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'name' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
            'expiration_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        if (! $product->tracksLots()) {
            return back()->withInput()->withErrors(['product_id' => 'Product tracking must be lot or serial.']);
        }

        Lot::query()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Lot created.');
    }
}
