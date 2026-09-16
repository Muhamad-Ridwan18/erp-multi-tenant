<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StockOperation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOperationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('inventory.operations.view'), 403);

        $type = in_array($request->query('type'), ['receipt', 'delivery', 'internal'], true)
            ? $request->query('type')
            : null;

        $operations = StockOperation::query()
            ->with(['customer', 'vendor', 'sourceLocation', 'destinationLocation'])
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest()
            ->get();

        return view('tenant.operations.index', compact('operations', 'type'));
    }

    public function show(Request $request, StockOperation $operation): View
    {
        abort_unless($request->user()->can('inventory.operations.view'), 403);

        $operation->load([
            'moves.product',
            'moves.uom',
            'customer',
            'vendor',
            'sourceLocation',
            'destinationLocation',
            'salesOrder',
            'purchaseOrder',
            'creator',
        ]);

        return view('tenant.operations.show', compact('operation'));
    }
}
