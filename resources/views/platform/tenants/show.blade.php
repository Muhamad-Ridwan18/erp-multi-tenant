@extends('layouts.app')

@section('title', $tenant->name)
@section('page-title', 'Tenant detail')
@section('page-subtitle', $tenant->slug)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-badge :tone="$tenant->status === 'active' ? 'success' : ($tenant->status === 'suspended' ? 'danger' : 'warning')">
                    {{ $tenant->status }}
                </x-badge>
                <span class="text-secondary">{{ $tenant->name }}</span>
                <span class="font-monospace small text-secondary">{{ $tenant->database }}</span>
            </div>
            <p class="mt-2 mb-0">
                <a href="{{ $tenant->domainUrl('/login') }}" target="_blank" rel="noopener">
                    {{ $tenant->domainUrl('/login') }}
                </a>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
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

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Plan</div>
                <div class="mt-2 fw-bold fs-4">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Users</div>
                <div class="mt-2 fw-bold fs-4">{{ $userCount }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Roles</div>
                <div class="mt-2 fw-bold fs-4">{{ $roleCount }}</div>
            </x-card>
        </div>
    </div>

    <x-card class="mt-3">
        <h2 class="h3 mb-3">Enabled modules</h2>
        @php
            $modules = $tenant->activeSubscription?->plan?->modules ?? collect();
        @endphp
        @if ($modules->isEmpty())
            <p class="mb-0 text-secondary">No active plan modules.</p>
        @else
            <div class="d-flex flex-wrap gap-2">
                @foreach ($modules as $module)
                    <x-badge tone="brand">{{ $module->name }}</x-badge>
                @endforeach
            </div>
        @endif
    </x-card>

    <div class="row g-3 mt-0">
        <div class="col-lg-6">
            <x-card :padding="false">
                <div class="card-header fw-medium">Users</div>
                <ul class="list-group list-group-flush">
                    @forelse ($users as $user)
                        <li class="list-group-item">
                            <div class="fw-medium">{{ $user->name }}</div>
                            <div class="text-secondary small">{{ $user->email }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">No users (or database not ready).</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <div class="col-lg-6">
            <x-card :padding="false">
                <div class="card-header fw-medium">Roles</div>
                <ul class="list-group list-group-flush">
                    @forelse ($roles as $role)
                        <li class="list-group-item d-flex align-items-center justify-content-between">
                            <span class="fw-medium">{{ $role->name }}</span>
                            @if ($role->is_system)
                                <x-badge>system</x-badge>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">No roles.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
@endsection
