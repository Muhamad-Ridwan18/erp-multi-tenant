@extends('layouts.app')

@section('title', 'Tenants')
@section('page-title', 'Tenants')
@section('page-subtitle', 'Companies renting Daksa ERP')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Tenants</h1>
            <p class="mt-1 text-sm text-ink-500">Platform view of all companies and their plans.</p>
        </div>
        <x-button href="{{ route('platform.tenants.create') }}">New tenant</x-button>
    </div>

    <x-table :headers="['Tenant', 'Status', 'Plan', 'Database', 'URL', '']">
        @forelse ($tenants as $tenant)
            <tr>
                <td class="px-4 py-3">
                    <div class="font-medium text-ink-900">{{ $tenant->name }}</div>
                    <div class="text-xs text-ink-400">{{ $tenant->slug }}</div>
                </td>
                <td class="px-4 py-3">
                    <x-badge :tone="$tenant->status === 'active' ? 'success' : ($tenant->status === 'suspended' ? 'danger' : 'warning')">
                        {{ $tenant->status }}
                    </x-badge>
                </td>
                <td class="px-4 py-3 text-ink-600">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-ink-600">{{ $tenant->database }}</td>
                <td class="px-4 py-3">
                    <a href="{{ $tenant->domainUrl('/login') }}" class="text-xs text-ink-700 underline" target="_blank" rel="noopener">
                        {{ $tenant->slug }}.{{ config('tenancy.base_host') }}
                    </a>
                </td>
                <td class="px-4 py-3 text-right">
                    <x-button href="{{ route('platform.tenants.show', $tenant) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-sm text-ink-500">No tenants yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection
