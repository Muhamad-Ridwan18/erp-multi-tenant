<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('settings.roles.view'), 403);

        $roles = Role::query()->withCount('permissions', 'users')->orderBy('name')->get();

        return view('tenant.roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('settings.roles.manage'), 403);

        return view('tenant.roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.roles.manage'), 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(
                    fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)
                ),
            ],
        ]);

        $role = Role::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $data['name'],
            'is_system' => false,
        ]);

        return redirect()
            ->route('tenant.roles.edit', $role)
            ->with('status', 'Role created. Assign permissions below.');
    }

    public function edit(Request $request, Role $role): View
    {
        abort_unless($request->user()->can('settings.roles.manage'), 403);
        abort_unless($role->tenant_id === $request->user()->tenant_id, 404);

        $tenant = $request->user()->tenant;
        $allowedModules = $tenant->enabledModuleCodes();

        $permissions = Permission::query()
            ->whereIn('module_code', $allowedModules)
            ->orderBy('module_code')
            ->orderBy('resource')
            ->orderBy('action')
            ->get()
            ->groupBy('module_code');

        $role->load('permissions', 'users');

        $tenantUsers = User::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name')
            ->get();

        return view('tenant.roles.edit', [
            'role' => $role,
            'permissionsByModule' => $permissions,
            'selected' => $role->permissions->pluck('id')->all(),
            'tenantUsers' => $tenantUsers,
            'selectedUsers' => $role->users->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->can('settings.roles.manage'), 403);
        abort_unless($role->tenant_id === $request->user()->tenant_id, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id))
                    ->ignore($role->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'users' => ['array'],
            'users.*' => [
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)
                ),
            ],
        ]);

        if (! $role->is_system) {
            $role->update(['name' => $data['name']]);
        }

        $allowedModules = $request->user()->tenant->enabledModuleCodes();
        $role->syncPermissionsWithinPlan($data['permissions'] ?? [], $allowedModules);
        $role->users()->sync($data['users'] ?? []);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->can('settings.roles.manage'), 403);
        abort_unless($role->tenant_id === $request->user()->tenant_id, 404);
        abort_if($role->is_system, 403, 'System roles cannot be deleted.');

        $role->users()->detach();
        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('tenant.roles.index')
            ->with('status', 'Role deleted.');
    }
}
