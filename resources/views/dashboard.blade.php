@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', $tenant?->name ?? 'Tenant workspace')

@section('content')
    <div class="mb-3">
        <p class="mb-0 text-secondary">
            Welcome, {{ $user->name }}. Roles: {{ $user->roles->pluck('name')->join(', ') ?: 'none' }}
        </p>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Tenant</div>
                <div class="mt-2 fw-bold fs-4">{{ $tenant?->name ?? '—' }}</div>
                <div class="mt-1"><x-badge tone="brand">{{ $tenant?->status ?? 'n/a' }}</x-badge></div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Roles</div>
                <div class="mt-2 fw-bold fs-4">{{ $user->roles->count() }}</div>
                <div class="mt-1 text-secondary small">Assigned to this user</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Permissions</div>
                <div class="mt-2 fw-bold fs-4">{{ $permissions->count() }}</div>
                <div class="mt-1 text-secondary small">Effective from roles</div>
            </x-card>
        </div>
    </div>

    <x-card class="mt-3">
        <div class="mb-3 d-flex align-items-center justify-content-between gap-2">
            <h2 class="h3 mb-0">Your permissions</h2>
            @can('settings.roles.view')
                <x-button href="{{ route('tenant.roles.index') }}" variant="secondary">Manage roles</x-button>
            @endcan
        </div>

        @if ($permissions->isEmpty())
            <p class="mb-0 text-secondary">No permissions assigned.</p>
        @else
            <div class="row g-2">
                @foreach ($permissions as $name)
                    <div class="col-sm-6">
                        <div class="bg-light rounded px-3 py-2 font-monospace small">{{ $name }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    <div class="mt-3 d-flex flex-wrap gap-2">
        @can('sales.orders.confirm')
            <x-badge tone="success">Can confirm sales orders</x-badge>
        @else
            <x-badge>Cannot confirm sales orders</x-badge>
        @endcan
        @cannot('settings.roles.view')
            <x-badge tone="warning">No access to manage roles</x-badge>
        @endcannot
    </div>
@endsection
