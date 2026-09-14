@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', $user->tenant?->name ?? 'Tenant workspace')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">Welcome, {{ $user->name }}</h1>
        <p class="mt-1 text-sm text-ink-500">
            Roles: {{ $user->roles->pluck('name')->join(', ') ?: 'none' }}
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Tenant</div>
            <div class="mt-2 text-lg font-semibold">{{ $user->tenant?->name ?? '—' }}</div>
            <div class="mt-1"><x-badge tone="brand">{{ $user->tenant?->status ?? 'n/a' }}</x-badge></div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Roles</div>
            <div class="mt-2 text-lg font-semibold">{{ $user->roles->count() }}</div>
            <div class="mt-1 text-sm text-ink-500">Assigned to this user</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Permissions</div>
            <div class="mt-2 text-lg font-semibold">{{ $permissions->count() }}</div>
            <div class="mt-1 text-sm text-ink-500">Effective from roles</div>
        </x-card>
    </div>

    <x-card class="mt-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="font-medium text-ink-900">Your permissions</h2>
            @can('settings.roles.view')
                <x-button href="{{ route('tenant.roles.index') }}" variant="secondary">Manage roles</x-button>
            @endcan
        </div>

        @if ($permissions->isEmpty())
            <p class="text-sm text-ink-500">No permissions assigned.</p>
        @else
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($permissions as $name)
                    <li class="rounded-lg bg-ink-50 px-3 py-2 font-mono text-xs text-ink-700">{{ $name }}</li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-4 flex flex-wrap gap-3 text-sm">
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
