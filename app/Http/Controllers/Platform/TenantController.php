<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $tenants = Tenant::query()
            ->with(['activeSubscription.plan', 'users'])
            ->withCount('users', 'roles')
            ->orderBy('name')
            ->get();

        return view('platform.tenants.index', compact('tenants'));
    }
}
