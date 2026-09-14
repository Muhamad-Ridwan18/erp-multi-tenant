@extends('layouts.app')

@section('title', 'Vendors')
@section('page-title', 'Vendors')
@section('page-subtitle', 'Procurement')

@section('page-actions')
    @can('procurement.vendors.create')
        <a href="{{ route('tenant.vendors.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New vendor
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Name', 'Email', 'Phone', '']" title="Vendors">
        @forelse ($vendors as $vendor)
            <tr>
                <td class="fw-medium">{{ $vendor->name }}</td>
                <td class="text-secondary">{{ $vendor->email ?: '—' }}</td>
                <td class="text-secondary">{{ $vendor->phone ?: '—' }}</td>
                <td class="text-end">
                    @can('procurement.vendors.update')
                        <a href="{{ route('tenant.vendors.edit', $vendor) }}" class="btn btn-ghost-primary btn-sm">Edit</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary py-4">No vendors yet.</td></tr>
        @endforelse
    </x-table>
@endsection
