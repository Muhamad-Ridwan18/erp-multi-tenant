@extends('layouts.app')

@section('title', 'Tenants')
@section('page-title', 'Tenants')
@section('page-subtitle', 'Companies renting Daksa ERP')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Platform view of all companies and their plans.</p>
        <x-button href="{{ route('platform.tenants.create') }}">New tenant</x-button>
    </div>

    <x-table :headers="['Tenant', 'Status', 'Plan', 'Database', 'URL', '']">
        @forelse ($tenants as $tenant)
            <tr>
                <td>
                    <div class="fw-medium">{{ $tenant->name }}</div>
                    <div class="small text-secondary">{{ $tenant->slug }}</div>
                </td>
                <td>
                    <x-badge :tone="$tenant->status === 'active' ? 'success' : ($tenant->status === 'suspended' ? 'danger' : 'warning')">
                        {{ $tenant->status }}
                    </x-badge>
                </td>
                <td class="text-secondary">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</td>
                <td class="font-monospace small text-secondary">{{ $tenant->database }}</td>
                <td>
                    <a href="{{ $tenant->domainUrl('/login') }}" class="small" target="_blank" rel="noopener">
                        {{ $tenant->slug }}.{{ config('tenancy.base_host') }}
                    </a>
                </td>
                <td class="text-end">
                    <x-button href="{{ route('platform.tenants.show', $tenant) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-secondary">No tenants yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection
