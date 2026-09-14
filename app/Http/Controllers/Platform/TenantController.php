<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $tenants = Tenant::query()
            ->with(['activeSubscription.plan'])
            ->withCount('users', 'roles')
            ->orderBy('name')
            ->get();

        return view('platform.tenants.index', compact('tenants'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $plans = Plan::query()->where('is_active', true)->orderBy('price_monthly')->get();

        return view('platform.tenants.create', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', 'alpha_dash', 'unique:tenants,slug'],
            'status' => ['required', Rule::in(['active', 'trial', 'suspended'])],
            'plan_id' => ['required', 'exists:plans,id'],
            'admin_name' => ['nullable', 'required_with:admin_email,admin_password', 'string', 'max:150'],
            'admin_email' => ['nullable', 'required_with:admin_name,admin_password', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['nullable', 'required_with:admin_name,admin_email', 'string', 'min:8'],
        ]);

        $tenant = DB::transaction(function () use ($data) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'status' => $data['status'],
                'trial_ends_at' => $data['status'] === 'trial' ? now()->addDays(14) : null,
            ]);

            $this->activatePlan($tenant, (int) $data['plan_id']);

            if (! empty($data['admin_email'])) {
                $this->provisionTenantAdmin(
                    $tenant,
                    $data['admin_name'],
                    $data['admin_email'],
                    $data['admin_password']
                );
            }

            return $tenant;
        });

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('status', 'Tenant created.');
    }

    public function show(Request $request, Tenant $tenant): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $tenant->load(['activeSubscription.plan.modules', 'users', 'roles'])
            ->loadCount('users', 'roles');

        return view('platform.tenants.show', compact('tenant'));
    }

    public function edit(Request $request, Tenant $tenant): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $plans = Plan::query()->where('is_active', true)->orderBy('price_monthly')->get();
        $tenant->load('activeSubscription');

        return view('platform.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'status' => ['required', Rule::in(['active', 'trial', 'suspended'])],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        DB::transaction(function () use ($tenant, $data) {
            $tenant->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'status' => $data['status'],
                'trial_ends_at' => $data['status'] === 'trial'
                    ? ($tenant->trial_ends_at ?? now()->addDays(14))
                    : $tenant->trial_ends_at,
            ]);

            $currentPlanId = $tenant->activeSubscription?->plan_id;
            if ((int) $currentPlanId !== (int) $data['plan_id']) {
                $this->activatePlan($tenant, (int) $data['plan_id']);
            }
        });

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('status', 'Tenant updated.');
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $tenant->update(['status' => 'suspended']);

        return back()->with('status', 'Tenant suspended.');
    }

    private function activatePlan(Tenant $tenant, int $planId): void
    {
        Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'ends_at' => now()]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);
    }

    private function provisionTenantAdmin(Tenant $tenant, string $name, string $email, string $password): void
    {
        TenantContext::set($tenant);

        try {
            $adminRole = Role::query()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin',
                'is_system' => true,
            ]);

            $enabledModules = $tenant->enabledModuleCodes();
            $permissionIds = Permission::query()
                ->whereIn('module_code', $enabledModules)
                ->pluck('id')
                ->all();

            $adminRole->permissions()->sync($permissionIds);

            $admin = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_platform_admin' => false,
            ]);

            $admin->roles()->sync([$adminRole->id]);
        } finally {
            TenantContext::clear();
        }
    }
}
