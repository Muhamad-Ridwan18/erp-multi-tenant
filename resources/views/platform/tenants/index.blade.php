@extends('layouts.app')

@section('title', 'Tenants')
@section('page-title', 'Tenants')
@section('page-subtitle', 'Platform')

@section('page-actions')
    <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i>
        New tenant
    </a>
@endsection

@section('content')
    <x-table :headers="['Tenant', 'Status', 'Plan', 'Database', 'URL', '']" title="All tenants">
        @forelse ($tenants as $tenant)
            <tr>
                <td>
                    <div class="fw-medium">{{ $tenant->name }}</div>
                    <div class="text-secondary">{{ $tenant->slug }}</div>
                </td>
                <td>
                    <x-badge :tone="$tenant->status === 'active' ? 'success' : ($tenant->status === 'suspended' ? 'danger' : 'warning')">
                        {{ $tenant->status }}
                    </x-badge>
                </td>
                <td class="text-secondary">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</td>
                <td class="font-monospace text-secondary">{{ $tenant->database }}</td>
                <td>
                    <a href="{{ $tenant->domainUrl('/login') }}" class="text-reset" target="_blank" rel="noopener">
                        {{ $tenant->slug }}.{{ config('tenancy.base_host') }}
                    </a>
                </td>
                <td class="text-end">
                    <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-ghost-primary btn-sm">View</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-secondary py-4">No tenants yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection
