@extends('layouts.app')

@section('title', 'Stock operations')
@section('page-title', 'Stock operations')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="mb-3 btn-list">
        <a href="{{ route('tenant.operations.index') }}" class="btn btn-sm {{ $type ? 'btn-ghost-secondary' : 'btn-primary' }}">All</a>
        <a href="{{ route('tenant.operations.index', ['type' => 'receipt']) }}" class="btn btn-sm {{ $type === 'receipt' ? 'btn-primary' : 'btn-ghost-secondary' }}">Receipts</a>
        <a href="{{ route('tenant.operations.index', ['type' => 'delivery']) }}" class="btn btn-sm {{ $type === 'delivery' ? 'btn-primary' : 'btn-ghost-secondary' }}">Deliveries</a>
        <a href="{{ route('tenant.operations.index', ['type' => 'internal']) }}" class="btn btn-sm {{ $type === 'internal' ? 'btn-primary' : 'btn-ghost-secondary' }}">Internal</a>
    </div>

    <x-table :headers="['Number', 'Type', 'Partner', 'From', 'To', 'Status', '']" title="Operations">
        @forelse ($operations as $operation)
            <tr>
                <td class="font-monospace small">{{ $operation->number }}</td>
                <td class="text-capitalize">{{ $operation->type }}</td>
                <td>{{ $operation->customer?->name ?? $operation->vendor?->name ?? '—' }}</td>
                <td class="text-secondary">{{ $operation->sourceLocation?->code ?? '—' }}</td>
                <td class="text-secondary">{{ $operation->destinationLocation?->code ?? '—' }}</td>
                <td>
                    <x-badge :tone="$operation->status === 'done' ? 'success' : ($operation->status === 'canceled' ? 'danger' : 'warning')">{{ $operation->status }}</x-badge>
                </td>
                <td class="text-end">
                    <a href="{{ route('tenant.operations.show', $operation) }}" class="btn btn-ghost-primary btn-sm">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary py-4">No stock operations yet.</td></tr>
        @endforelse
    </x-table>
@endsection
