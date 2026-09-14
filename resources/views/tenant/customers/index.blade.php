@extends('layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers')
@section('page-subtitle', 'Partners')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Companies and people you sell to.</p>
        @can('sales.customers.create')
            <x-button href="{{ route('tenant.customers.create') }}">New customer</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Email', 'Phone', '']">
        @forelse ($customers as $customer)
            <tr>
                <td class="fw-medium">{{ $customer->name }}</td>
                <td class="text-secondary">{{ $customer->email ?: '—' }}</td>
                <td class="text-secondary">{{ $customer->phone ?: '—' }}</td>
                <td class="text-end">
                    @can('sales.customers.update')
                        <x-button href="{{ route('tenant.customers.edit', $customer) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary">No customers yet.</td></tr>
        @endforelse
    </x-table>
@endsection
