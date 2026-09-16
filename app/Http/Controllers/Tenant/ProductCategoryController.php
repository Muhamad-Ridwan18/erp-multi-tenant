<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('settings.categories.manage'), 403);

        $categories = ProductCategory::query()
            ->with('parent')
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('tenant.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.categories.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique(ProductCategory::class, 'name')],
            'parent_id' => ['nullable', Rule::exists(ProductCategory::class, 'id')],
        ]);

        ProductCategory::query()->create($data);

        return redirect()
            ->route('tenant.categories.index')
            ->with('status', 'Category created.');
    }
}
