<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('finance.taxes.view'), 403);

        $taxes = Tax::query()
            ->with('taxGroup')
            ->orderBy('name')
            ->get();

        return view('tenant.taxes.index', compact('taxes'));
    }
}
