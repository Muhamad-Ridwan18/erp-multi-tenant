@extends('layouts.app')

@section('title', 'Purchase orders')
@section('page-title', 'Purchase orders')
@section('page-subtitle', 'Procurement')

@section('page-actions')
    @can('procurement.orders.create')
        <a href="{{ route('tenant.purchases.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New PO
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Number', 'Vendor', 'Status', 'Total', '']" title="Purchase orders">
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
                    <a href="{{ route('tenant.purchases.show', $order) }}" class="btn btn-ghost-primary btn-sm">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary py-4">No purchase orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
