@extends('layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers')
@section('page-subtitle', 'Partners')

@section('page-actions')
    @can('sales.customers.create')
        <a href="{{ route('tenant.customers.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New customer
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Name', 'Email', 'Phone', '']" title="Customers">
        @forelse ($customers as $customer)
            <tr>
                <td class="fw-medium">{{ $customer->name }}</td>
                <td class="text-secondary">{{ $customer->email ?: '—' }}</td>
                <td class="text-secondary">{{ $customer->phone ?: '—' }}</td>
                <td class="text-end">
                    @can('sales.customers.update')
                        <a href="{{ route('tenant.customers.edit', $customer) }}" class="btn btn-ghost-primary btn-sm">Edit</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary py-4">No customers yet.</td></tr>
        @endforelse
    </x-table>
@endsection
