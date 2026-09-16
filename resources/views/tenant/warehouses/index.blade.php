@extends('layouts.app')

@section('title', 'Warehouses')
@section('page-title', 'Warehouses')
@section('page-subtitle', 'Inventory')

@section('page-actions')
    @can('inventory.warehouses.manage')
        <a href="{{ route('tenant.warehouses.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New warehouse
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Code', 'Name', 'Address', 'Locations', 'Status']" title="Warehouses">
        @forelse ($warehouses as $warehouse)
            <tr>
                <td class="font-monospace small">{{ $warehouse->code }}</td>
                <td class="fw-medium">{{ $warehouse->name }}</td>
                <td class="text-secondary">{{ $warehouse->address ?: '—' }}</td>
                <td class="text-secondary">{{ $warehouse->locations->pluck('code')->implode(', ') ?: '—' }}</td>
                <td>
                    <x-badge :tone="$warehouse->is_active ? 'success' : 'neutral'">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</x-badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary py-4">No warehouses yet.</td></tr>
        @endforelse
    </x-table>
@endsection
