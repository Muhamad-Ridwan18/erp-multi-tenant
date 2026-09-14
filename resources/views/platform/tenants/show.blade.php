@extends('layouts.app')

@section('title', $tenant->name)
@section('page-title', 'Tenant detail')
@section('page-subtitle', $tenant->slug)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">{{ $tenant->name }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-badge :tone="$tenant->status === 'active' ? 'success' : ($tenant->status === 'suspended' ? 'danger' : 'warning')">
                    {{ $tenant->status }}
                </x-badge>
                <span class="text-sm text-ink-500">{{ $tenant->slug }}</span>
                <span class="font-mono text-xs text-ink-400">{{ $tenant->database }}</span>
            </div>
            <p class="mt-2 text-sm">
                <a href="{{ $tenant->domainUrl('/login') }}" class="text-ink-700 underline" target="_blank" rel="noopener">
                    {{ $tenant->domainUrl('/login') }}
                </a>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button href="{{ route('platform.tenants.edit', $tenant) }}" variant="secondary">Edit</x-button>
            @if ($tenant->status !== 'suspended')
                <form method="POST" action="{{ route('platform.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspend this tenant?')">
                    @csrf
                    @method('PATCH')
                    <x-button type="submit" variant="danger">Suspend</x-button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Plan</div>
            <div class="mt-2 text-lg font-semibold">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Users</div>
            <div class="mt-2 text-lg font-semibold">{{ $userCount }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Roles</div>
            <div class="mt-2 text-lg font-semibold">{{ $roleCount }}</div>
        </x-card>
    </div>

    <x-card class="mt-6">
        <h2 class="mb-3 font-medium text-ink-900">Enabled modules</h2>
        @php
            $modules = $tenant->activeSubscription?->plan?->modules ?? collect();
        @endphp
        @if ($modules->isEmpty())
            <p class="text-sm text-ink-500">No active plan modules.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($modules as $module)
                    <x-badge tone="brand">{{ $module->name }}</x-badge>
                @endforeach
            </div>
        @endif
    </x-card>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-card :padding="false">
            <div class="border-b border-line px-6 py-4 font-medium text-ink-900">Users</div>
            <ul class="divide-y divide-line">
                @forelse ($users as $user)
                    <li class="px-6 py-3 text-sm">
                        <div class="font-medium text-ink-900">{{ $user->name }}</div>
                        <div class="text-ink-500">{{ $user->email }}</div>
                    </li>
                @empty
                    <li class="px-6 py-6 text-sm text-ink-500">No users (or database not ready).</li>
                @endforelse
            </ul>
        </x-card>

        <x-card :padding="false">
            <div class="border-b border-line px-6 py-4 font-medium text-ink-900">Roles</div>
            <ul class="divide-y divide-line">
                @forelse ($roles as $role)
                    <li class="flex items-center justify-between px-6 py-3 text-sm">
                        <span class="font-medium text-ink-900">{{ $role->name }}</span>
                        @if ($role->is_system)
                            <x-badge>system</x-badge>
                        @endif
                    </li>
                @empty
                    <li class="px-6 py-6 text-sm text-ink-500">No roles.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
@endsection
