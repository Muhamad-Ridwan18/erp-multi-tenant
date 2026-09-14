@extends('layouts.app')

@section('title', 'Sales orders')
@section('page-title', 'Sales orders')
@section('page-subtitle', 'Sales')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Sales orders</h1>
            <p class="mt-1 text-sm text-ink-500">Draft then confirm to deduct stock.</p>
        </div>
        @can('sales.orders.create')
            <x-button href="{{ route('tenant.orders.create') }}">New order</x-button>
        @endcan
    </div>

    <x-table :headers="['Number', 'Customer', 'Status', 'Subtotal', '']">
        @forelse ($orders as $order)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-ink-800">{{ $order->number }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $order->customer?->name }}</td>
                <td class="px-4 py-3">
                    <x-badge :tone="$order->status === 'confirmed' ? 'success' : 'warning'">{{ $order->status }}</x-badge>
                </td>
                <td class="px-4 py-3 text-ink-700">{{ $order->formattedSubtotal() }}</td>
                <td class="px-4 py-3 text-right">
                    <x-button href="{{ route('tenant.orders.show', $order) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-ink-500">No orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
