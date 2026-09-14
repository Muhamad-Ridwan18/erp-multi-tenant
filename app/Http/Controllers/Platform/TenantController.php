<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        protected TenantProvisioner $provisioner,
        protected TenantDatabaseManager $databases,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $tenants = Tenant::query()
            ->with(['activeSubscription.plan'])
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
            'slug' => ['required', 'string', 'max:150', 'alpha_dash', Rule::unique(Tenant::class, 'slug')],
            'status' => ['required', Rule::in(['active', 'trial', 'suspended'])],
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
            'admin_name' => ['nullable', 'required_with:admin_email,admin_password', 'string', 'max:150'],
            'admin_email' => ['nullable', 'required_with:admin_name,admin_password', 'email', 'max:150'],
            'admin_password' => ['nullable', 'required_with:admin_name,admin_email', 'string', 'min:8'],
        ]);

        $tenant = $this->provisioner->provision($data);

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('status', 'Tenant created and database provisioned.');
    }

    public function show(Request $request, Tenant $tenant): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $tenant->load(['activeSubscription.plan.modules']);

        $userCount = 0;
        $roleCount = 0;
        $users = collect();
        $roles = collect();

        try {
            $this->databases->connect($tenant);
            $userCount = User::query()->count();
            $roleCount = \App\Models\Role::query()->count();
            $users = User::query()->orderBy('name')->limit(50)->get();
            $roles = \App\Models\Role::query()->orderBy('name')->get();
        } catch (\Throwable) {
            // Tenant DB may not exist yet.
        } finally {
            $this->databases->disconnect();
        }

        return view('platform.tenants.show', compact('tenant', 'userCount', 'roleCount', 'users', 'roles'));
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
            'slug' => ['required', 'string', 'max:150', 'alpha_dash', Rule::unique(Tenant::class, 'slug')->ignore($tenant->id)],
            'status' => ['required', Rule::in(['active', 'trial', 'suspended'])],
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
        ]);

        DB::connection('central')->transaction(function () use ($tenant, $data) {
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
                $this->provisioner->activatePlan($tenant, (int) $data['plan_id']);
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
}
