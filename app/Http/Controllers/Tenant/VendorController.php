<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('procurement.vendors.view'), 403);

        $vendors = Vendor::query()->orderBy('name')->get();

        return view('tenant.vendors.index', compact('vendors'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('procurement.vendors.create'), 403);

        return view('tenant.vendors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.vendors.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        Vendor::query()->create($data);

        return redirect()
            ->route('tenant.vendors.index')
            ->with('status', 'Vendor created.');
    }

    public function edit(Request $request, Vendor $vendor): View
    {
        abort_unless($request->user()->can('procurement.vendors.update'), 403);

        return view('tenant.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.vendors.update'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $vendor->update($data);

        return redirect()
            ->route('tenant.vendors.index')
            ->with('status', 'Vendor updated.');
    }

    public function destroy(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($request->user()->can('procurement.vendors.delete'), 403);

        if ($vendor->purchaseOrders()->exists()) {
            return back()->withErrors(['vendor' => 'Vendor has purchase orders and cannot be deleted.']);
        }

        $vendor->delete();

        return redirect()
            ->route('tenant.vendors.index')
            ->with('status', 'Vendor deleted.');
    }
}
