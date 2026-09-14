@extends('layouts.app')

@section('title', 'Sales orders')
@section('page-title', 'Sales orders')
@section('page-subtitle', 'Sales')

@section('page-actions')
    @can('sales.orders.create')
        <a href="{{ route('tenant.orders.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New order
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Number', 'Customer', 'Status', 'Total', '']" title="Sales orders">
        @forelse ($orders as $order)
            <tr>
                <td class="font-monospace small">{{ $order->number }}</td>
                <td>{{ $order->customer?->name }}</td>
                <td>
                    <x-badge :tone="$order->status === 'confirmed' ? 'success' : 'warning'">{{ $order->status }}</x-badge>
                </td>
                <td>{{ $order->formattedGrandTotal() }}</td>
                <td class="text-end">
                    <a href="{{ route('tenant.orders.show', $order) }}" class="btn btn-ghost-primary btn-sm">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary py-4">No orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
