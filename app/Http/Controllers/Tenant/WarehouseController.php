<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.warehouses.view'), 403);

        $warehouses = Warehouse::query()
            ->with('locations')
            ->orderBy('code')
            ->get();

        return view('tenant.warehouses.index', compact('warehouses'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('inventory.warehouses.manage'), 403);

        return view('tenant.warehouses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('inventory.warehouses.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique(Warehouse::class, 'code')],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::connection('tenant')->transaction(function () use ($data, $request) {
            $warehouse = Warehouse::query()->create([
                ...$data,
                'is_active' => $request->boolean('is_active', true),
            ]);

            Location::query()->firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => 'STOCK'],
                ['name' => $warehouse->name.' / Stock', 'type' => 'internal', 'is_active' => true]
            );
        });

        return redirect()
            ->route('tenant.warehouses.index')
            ->with('status', 'Warehouse created with a stock location.');
    }
}
