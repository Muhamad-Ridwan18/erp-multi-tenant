@extends('layouts.app')

@section('title', 'Manufacturing orders')
@section('page-title', 'Manufacturing orders')
@section('page-subtitle', 'Manufacturing')

@section('page-actions')
    @can('manufacturing.orders.create')
        <a href="{{ route('tenant.manufacturing-orders.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New MO
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Number', 'Product', 'Qty', 'Produced', 'Work center', 'Status']" title="Orders">
        @forelse ($orders as $order)
            <tr>
                <td class="font-monospace small">
                    <a href="{{ route('tenant.manufacturing-orders.show', $order) }}">{{ $order->number }}</a>
                </td>
                <td class="fw-medium">{{ $order->product?->name }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->qty_produced }}</td>
                <td class="text-secondary">{{ $order->workCenter?->name ?: '—' }}</td>
                <td><x-badge :tone="$order->status === 'done' ? 'success' : ($order->status === 'confirmed' ? 'brand' : 'warning')">{{ $order->status }}</x-badge></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">No manufacturing orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
