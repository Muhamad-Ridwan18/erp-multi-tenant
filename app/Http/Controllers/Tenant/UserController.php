<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('settings.users.view'), 403);

        $users = User::query()->with('roles')->orderBy('name')->get();

        return view('tenant.users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('settings.users.manage'), 403);

        $roles = Role::query()->orderBy('name')->get();

        return view('tenant.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.users.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists(Role::class, 'id')],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->roles()->sync($data['roles'] ?? []);

        return redirect()
            ->route('tenant.users.index')
            ->with('status', 'User created.');
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()->can('settings.users.manage'), 403);

        $roles = Role::query()->orderBy('name')->get();
        $user->load('roles');

        return view('tenant.users.edit', [
            'userModel' => $user,
            'roles' => $roles,
            'selectedRoles' => $user->roles->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('settings.users.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists(Role::class, 'id')],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->roles()->sync($data['roles'] ?? []);

        return redirect()
            ->route('tenant.users.index')
            ->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('settings.users.manage'), 403);
        abort_if($user->id === $request->user()->id, 403, 'You cannot delete your own account.');

        $user->roles()->detach();
        $user->delete();

        return redirect()
            ->route('tenant.users.index')
            ->with('status', 'User deleted.');
    }
}
