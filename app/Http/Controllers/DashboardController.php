<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('roles.permissions', 'tenant');

        return view('dashboard', [
            'user' => $user,
            'permissions' => $user->permissionNames(),
        ]);
    }
}
