<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('roles.permissions');

        return view('dashboard', [
            'user' => $user,
            'tenant' => TenantContext::get(),
            'permissions' => $user->permissionNames(),
        ]);
    }
}
