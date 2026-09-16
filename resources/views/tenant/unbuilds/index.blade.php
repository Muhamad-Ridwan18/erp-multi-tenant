@extends('layouts.app')

@section('title', 'Unbuild orders')
@section('page-title', 'Unbuild orders')
@section('page-subtitle', 'Manufacturing')

@section('page-actions')
    @can('manufacturing.unbuilds.create')
        <a href="{{ route('tenant.unbuilds.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i> New unbuild</a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Number', 'Product', 'Qty', 'Status']" title="Unbuilds">
        @forelse ($orders as $order)
            <tr>
                <td class="font-monospace small"><a href="{{ route('tenant.unbuilds.show', $order) }}">{{ $order->number }}</a></td>
                <td class="fw-medium">{{ $order->product?->name }}</td>
                <td>{{ $order->quantity }}</td>
                <td><x-badge :tone="$order->status === 'done' ? 'success' : 'warning'">{{ $order->status }}</x-badge></td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary py-4">No unbuild orders yet.</td></tr>
        @endforelse
    </x-table>
@endsection
