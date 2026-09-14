@extends('layouts.app')

@section('title', 'Vendors')
@section('page-title', 'Vendors')
@section('page-subtitle', 'Procurement')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Vendors</h1>
            <p class="mt-1 text-sm text-ink-500">Suppliers for purchase orders.</p>
        </div>
        @can('procurement.vendors.create')
            <x-button href="{{ route('tenant.vendors.create') }}">New vendor</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Email', 'Phone', '']">
        @forelse ($vendors as $vendor)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $vendor->name }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $vendor->email ?: '—' }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $vendor->phone ?: '—' }}</td>
                <td class="px-4 py-3 text-right">
                    @can('procurement.vendors.update')
                        <x-button href="{{ route('tenant.vendors.edit', $vendor) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-ink-500">No vendors yet.</td></tr>
        @endforelse
    </x-table>
@endsection
