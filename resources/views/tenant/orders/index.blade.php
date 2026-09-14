@extends('layouts.app')

@section('title', 'Sales orders')
@section('page-title', 'Sales orders')
@section('page-subtitle', 'Sales')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Draft then confirm to deduct stock.</p>
        @can('sales.orders.create')
            <x-button href="{{ route('tenant.orders.create') }}">New order</x-button>
        @endcan
    </div>

    <x-table :headers="['Number', 'Customer', 'Status', 'Total', '']">
        @forelse ($orders as $order)
            <tr>
                <td class="font-monospace small">{{ $order->number }}</td>
                <td>{{ $order->customer?->name }}</td>
                <td>
                    <x-badge :tone="$order->status === 'confirmed' ? 'success' : 'warning'">{{ $order->status }}</x-badge>
                </td>
                <td>{{ $order->formattedGrandTotal() }}</td>
                <td class="text-end">
                    <x-button href="{{ route('tenant.orders.show', $order) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary">No orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
