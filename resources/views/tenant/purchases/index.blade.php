@extends('layouts.app')

@section('title', 'Purchase orders')
@section('page-title', 'Purchase orders')
@section('page-subtitle', 'Procurement')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Draft → confirm → receive goods into stock.</p>
        @can('procurement.orders.create')
            <x-button href="{{ route('tenant.purchases.create') }}">New PO</x-button>
        @endcan
    </div>

    <x-table :headers="['Number', 'Vendor', 'Status', 'Total', '']">
        @forelse ($orders as $order)
            <tr>
                <td class="font-monospace small">{{ $order->number }}</td>
                <td>{{ $order->vendor?->name }}</td>
                <td>
                    @php
                        $tone = match ($order->status) {
                            'received' => 'success',
                            'confirmed' => 'brand',
                            default => 'warning',
                        };
                    @endphp
                    <x-badge :tone="$tone">{{ $order->status }}</x-badge>
                </td>
                <td>{{ $order->formattedGrandTotal() }}</td>
                <td class="text-end">
                    <x-button href="{{ route('tenant.purchases.show', $order) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary">No purchase orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
