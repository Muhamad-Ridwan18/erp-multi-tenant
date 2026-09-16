@extends('layouts.app')

@section('title', $operation->number)
@section('page-title', 'Stock operation')
@section('page-subtitle', $operation->number)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <x-badge :tone="$operation->status === 'done' ? 'success' : ($operation->status === 'canceled' ? 'danger' : 'warning')">{{ $operation->status }}</x-badge>
            <span class="text-secondary text-capitalize">{{ $operation->type }}</span>
            @if ($operation->purchaseOrder)
                <a href="{{ route('tenant.purchases.show', $operation->purchaseOrder) }}" class="small">{{ $operation->purchaseOrder->number }}</a>
            @endif
            @if ($operation->salesOrder)
                <a href="{{ route('tenant.orders.show', $operation->salesOrder) }}" class="small">{{ $operation->salesOrder->number }}</a>
            @endif
        </div>
        <x-button href="{{ route('tenant.operations.index') }}" variant="ghost">Back</x-button>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Partner</div>
                <div class="mt-2 fw-bold">{{ $operation->customer?->name ?? $operation->vendor?->name ?? '—' }}</div>
                <div class="text-secondary small">Origin {{ $operation->origin ?: '—' }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Route</div>
                <div class="mt-2 fw-bold">{{ $operation->sourceLocation?->code ?? '—' }} → {{ $operation->destinationLocation?->code ?? '—' }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Validated</div>
                <div class="mt-2 fw-bold">{{ $operation->done_at?->format('d M Y H:i') ?? '—' }}</div>
                <div class="text-secondary small">By {{ $operation->creator?->name ?? '—' }}</div>
            </x-card>
        </div>
    </div>

    <x-table class="mt-3" :headers="['Product', 'Unit', 'Demand', 'Done']">
        @foreach ($operation->moves as $move)
            <tr>
                <td class="fw-medium">{{ $move->product?->name }}</td>
                <td class="text-secondary">{{ $move->uom?->code ?? $move->product?->unit }}</td>
                <td>{{ $move->demand_qty }}</td>
                <td>{{ $move->done_qty }}</td>
            </tr>
        @endforeach
    </x-table>
@endsection
