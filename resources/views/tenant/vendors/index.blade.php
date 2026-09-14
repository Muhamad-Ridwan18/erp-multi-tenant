@extends('layouts.app')

@section('title', 'Vendors')
@section('page-title', 'Vendors')
@section('page-subtitle', 'Procurement')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Suppliers for purchase orders.</p>
        @can('procurement.vendors.create')
            <x-button href="{{ route('tenant.vendors.create') }}">New vendor</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Email', 'Phone', '']">
        @forelse ($vendors as $vendor)
            <tr>
                <td class="fw-medium">{{ $vendor->name }}</td>
                <td class="text-secondary">{{ $vendor->email ?: '—' }}</td>
                <td class="text-secondary">{{ $vendor->phone ?: '—' }}</td>
                <td class="text-end">
                    @can('procurement.vendors.update')
                        <x-button href="{{ route('tenant.vendors.edit', $vendor) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary">No vendors yet.</td></tr>
        @endforelse
    </x-table>
@endsection
