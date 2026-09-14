@extends('layouts.app')

@section('title', 'Purchase orders')
@section('page-title', 'Purchase orders')
@section('page-subtitle', 'Procurement')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Purchase orders</h1>
            <p class="mt-1 text-sm text-ink-500">Draft → confirm → receive goods into stock.</p>
        </div>
        @can('procurement.orders.create')
            <x-button href="{{ route('tenant.purchases.create') }}">New PO</x-button>
        @endcan
    </div>

    <x-table :headers="['Number', 'Vendor', 'Status', 'Total', '']">
        @forelse ($orders as $order)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-ink-800">{{ $order->number }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $order->vendor?->name }}</td>
                <td class="px-4 py-3">
                    @php
                        $tone = match ($order->status) {
                            'received' => 'success',
                            'confirmed' => 'brand',
                            default => 'warning',
                        };
                    @endphp
                    <x-badge :tone="$tone">{{ $order->status }}</x-badge>
                </td>
                <td class="px-4 py-3 text-ink-700">{{ $order->formattedGrandTotal() }}</td>
                <td class="px-4 py-3 text-right">
                    <x-button href="{{ route('tenant.purchases.show', $order) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-ink-500">No purchase orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
