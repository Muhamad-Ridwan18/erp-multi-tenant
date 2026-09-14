<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('sales.customers.view'), 403);

        $customers = Customer::query()->orderBy('name')->get();

        return view('tenant.customers.index', compact('customers'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('sales.customers.create'), 403);

        return view('tenant.customers.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('sales.customers.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::query()->create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ]);
        }

        return redirect()
            ->route('tenant.customers.index')
            ->with('status', 'Customer created.');
    }

    public function quick(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('sales.customers.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = Customer::query()->create($data);

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
        ]);
    }

    public function edit(Request $request, Customer $customer): View
    {
        abort_unless($request->user()->can('sales.customers.update'), 403);

        return view('tenant.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->can('sales.customers.update'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer->update($data);

        return redirect()
            ->route('tenant.customers.index')
            ->with('status', 'Customer updated.');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->can('sales.customers.delete'), 403);

        if ($customer->salesOrders()->exists()) {
            return back()->withErrors(['customer' => 'Customer has sales orders and cannot be deleted.']);
        }

        $customer->delete();

        return redirect()
            ->route('tenant.customers.index')
            ->with('status', 'Customer deleted.');
    }
}
