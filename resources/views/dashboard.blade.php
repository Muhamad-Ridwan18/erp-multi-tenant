@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', $tenant?->name ?? 'Tenant workspace')

@section('page-actions')
    @can('settings.roles.view')
        <a href="{{ route('tenant.roles.index') }}" class="btn btn-outline-primary">
            <i class="ti ti-shield-lock me-1"></i>
            Manage roles
        </a>
    @endcan
@endsection

@section('content')
    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Tenant</div>
                    <div class="h1 mb-1">{{ $tenant?->name ?? '—' }}</div>
                    <div><x-badge tone="brand">{{ $tenant?->status ?? 'n/a' }}</x-badge></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Roles</div>
                    <div class="h1 mb-1">{{ $user->roles->count() }}</div>
                    <div class="text-secondary">Assigned to {{ $user->name }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Permissions</div>
                    <div class="h1 mb-1">{{ $permissions->count() }}</div>
                    <div class="text-secondary">Effective from roles</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Your permissions</h3>
        </div>
        <div class="card-body">
            @if ($permissions->isEmpty())
                <p class="mb-0 text-secondary">No permissions assigned.</p>
            @else
                <div class="row g-2">
                    @foreach ($permissions as $name)
                        <div class="col-md-6 col-xl-4">
                            <div class="bg-secondary-lt rounded px-3 py-2 font-monospace" style="font-size: .8rem">{{ $name }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
