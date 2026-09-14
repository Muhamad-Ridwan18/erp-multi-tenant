@extends('layouts.app')

@section('title', 'Create tenant')
@section('page-title', 'Create tenant')
@section('page-subtitle', 'Provision a new company')

@section('content')
    <div class="max-w-2xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">Create tenant</h1>

        <form method="POST" action="{{ route('platform.tenants.store') }}" class="space-y-6">
            @csrf

            <x-card class="space-y-4">
                <h2 class="font-medium text-ink-900">Company</h2>
                <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus data-slug-source="tenant" />
                <x-input label="Slug" name="slug" value="{{ old('slug') }}" required help="Becomes {{ config('tenancy.base_host') === 'localhost' ? 'slug.localhost' : 'slug.'.config('tenancy.base_host') }} and DB {{ config('tenancy.database_prefix') }}{slug}." data-slug-target="tenant" />
                <div class="space-y-1.5">
                    <label for="status" class="block text-sm font-medium text-ink-800">Status</label>
                    <select id="status" name="status" class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ink-400/40">
                        @foreach (['active', 'trial', 'suspended'] as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="plan_id" class="block text-sm font-medium text-ink-800">Plan</label>
                    <select id="plan_id" name="plan_id" required class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ink-400/40">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>
                                {{ $plan->name }} — Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}/mo
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-card>

            <x-card class="space-y-4">
                <div>
                    <h2 class="font-medium text-ink-900">Initial admin (optional)</h2>
                    <p class="mt-1 text-sm text-ink-500">Creates a system Admin role with all plan permissions.</p>
                </div>
                <x-input label="Admin name" name="admin_name" value="{{ old('admin_name') }}" />
                <x-input label="Admin email" name="admin_email" type="email" value="{{ old('admin_email') }}" />
                <x-input label="Admin password" name="admin_password" type="password" help="Minimum 8 characters." />
            </x-card>

            <div class="flex items-center gap-3">
                <x-button>Create tenant</x-button>
                <x-button href="{{ route('platform.tenants.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>
@endsection
