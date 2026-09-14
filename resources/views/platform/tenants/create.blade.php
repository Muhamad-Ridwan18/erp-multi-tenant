@extends('layouts.app')

@section('title', 'Create tenant')
@section('page-title', 'Create tenant')
@section('page-subtitle', 'Provision a new company')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('platform.tenants.store') }}" class="vstack gap-3">
                @csrf

                <x-card>
                    <h2 class="h3 mb-3">Company</h2>
                    <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus data-slug-source="tenant" />
                    <x-input label="Slug" name="slug" value="{{ old('slug') }}" required help="Becomes {{ config('tenancy.base_host') === 'localhost' ? 'slug.localhost' : 'slug.'.config('tenancy.base_host') }} and DB {{ config('tenancy.database_prefix') }}{slug}." data-slug-target="tenant" />
                    <x-select label="Status" name="status" :searchable="false">
                        @foreach (['active', 'trial', 'suspended'] as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Plan" name="plan_id" required :searchable="false">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>
                                {{ $plan->name }} — Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}/mo
                            </option>
                        @endforeach
                    </x-select>
                </x-card>

                <x-card>
                    <h2 class="h3 mb-1">Initial admin (optional)</h2>
                    <p class="text-secondary mb-3">Creates a system Admin role with all plan permissions.</p>
                    <x-input label="Admin name" name="admin_name" value="{{ old('admin_name') }}" />
                    <x-input label="Admin email" name="admin_email" type="email" value="{{ old('admin_email') }}" />
                    <x-input label="Admin password" name="admin_password" type="password" help="Minimum 8 characters." />
                </x-card>

                <div class="d-flex align-items-center gap-2">
                    <x-button>Create tenant</x-button>
                    <x-button href="{{ route('platform.tenants.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
