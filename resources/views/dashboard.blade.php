@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold mb-1">Dashboard</h1>
    <p class="text-slate-500 text-sm mb-6">
        Tenant: <strong>{{ $user->tenant?->name ?? '—' }}</strong>
        · Roles: {{ $user->roles->pluck('name')->join(', ') ?: 'none' }}
    </p>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-medium mb-3">Your permissions</h2>
        @if ($permissions->isEmpty())
            <p class="text-sm text-slate-500">No permissions assigned.</p>
        @else
            <ul class="grid sm:grid-cols-2 gap-2 text-sm">
                @foreach ($permissions as $name)
                    <li class="rounded-md bg-slate-50 px-3 py-2 font-mono text-xs">{{ $name }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-4 flex gap-3 text-sm">
        @can('settings.roles.view')
            <a href="{{ route('tenant.roles.index') }}" class="text-blue-700 underline">Manage roles</a>
        @else
            <span class="text-slate-400">No access to manage roles</span>
        @endcan
        @can('sales.orders.confirm')
            <span class="text-emerald-700">Can confirm sales orders</span>
        @else
            <span class="text-slate-400">Cannot confirm sales orders</span>
        @endcan
    </div>
@endsection
