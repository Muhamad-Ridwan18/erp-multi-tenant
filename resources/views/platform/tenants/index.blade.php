@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
    <h1 class="text-2xl font-semibold mb-1">Tenants</h1>
    <p class="text-sm text-slate-500 mb-6">Platform view — companies renting Daksa ERP.</p>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left">
                <tr>
                    <th class="px-4 py-3">Tenant</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3">Roles</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tenants as $tenant)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $tenant->name }}</div>
                            <div class="text-xs text-slate-400">{{ $tenant->slug }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $tenant->status }}</td>
                        <td class="px-4 py-3">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $tenant->users_count }}</td>
                        <td class="px-4 py-3">{{ $tenant->roles_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
