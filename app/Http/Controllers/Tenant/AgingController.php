<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AgingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgingController extends Controller
{
    public function index(Request $request, AgingService $aging): View
    {
        abort_unless($request->user()->can('finance.aging.view'), 403);

        $type = $request->string('type')->toString() === 'ap' ? 'ap' : 'ar';
        $report = $type === 'ap' ? $aging->payables() : $aging->receivables();

        return view('tenant.aging.index', [
            'type' => $type,
            'report' => $report,
        ]);
    }
}
