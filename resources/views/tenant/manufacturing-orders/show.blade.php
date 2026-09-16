@extends('layouts.app')

@section('title', $order->number)
@section('page-title', 'Manufacturing order')
@section('page-subtitle', $order->number)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <x-badge :tone="$order->status === 'done' ? 'success' : ($order->status === 'confirmed' ? 'brand' : 'warning')">{{ $order->status }}</x-badge>
            <span class="fw-medium">{{ $order->product?->name }}</span>
            <span class="text-secondary">{{ $order->qty_produced }}/{{ $order->quantity }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($order->isDraft())
                @can('manufacturing.orders.confirm')
                    <form method="POST" action="{{ route('tenant.manufacturing-orders.confirm', $order) }}">
                        @csrf
                        <x-button type="submit">Confirm</x-button>
                    </form>
                @endcan
            @endif
            @if ($order->isDraft() || $order->isConfirmed())
                @can('manufacturing.orders.produce')
                    <form method="POST" action="{{ route('tenant.manufacturing-orders.produce', $order) }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        <input type="number" name="quantity" class="form-control" style="width: 6rem" min="1" max="{{ $order->quantity - $order->qty_produced }}" placeholder="Qty" value="{{ $order->quantity - $order->qty_produced }}">
                        @if ($order->product?->tracksLots())
                            <input type="text" name="lot_name" class="form-control" style="width: 8rem" placeholder="Lot name" value="{{ old('lot_name') }}">
                        @endif
                        <x-button type="submit">Produce</x-button>
                    </form>
                @endcan
            @endif
            @if ($order->isDraft())
                @can('manufacturing.orders.update')
                    <form method="POST" action="{{ route('tenant.manufacturing-orders.cancel', $order) }}" onsubmit="return confirm('Cancel this MO?')">
                        @csrf
                        <x-button type="submit" variant="danger">Cancel</x-button>
                    </form>
                @endcan
            @endif
            <x-button href="{{ route('tenant.manufacturing-orders.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <x-card>
                <div class="text-secondary text-uppercase small">BOM</div>
                <div class="mt-2 fw-bold">{{ $order->billOfMaterial?->code ?: '#'.$order->bill_of_material_id }}</div>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Work center</div>
                <div class="mt-2 fw-bold">{{ $order->workCenter?->name ?: '—' }}</div>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Origin</div>
                <div class="mt-2 fw-bold">{{ $order->origin ?: '—' }}</div>
            </x-card>
        </div>
    </div>

    <x-table :headers="['Component', 'To consume', 'Consumed']" title="Components">
        @foreach ($order->components as $component)
            <tr>
                <td class="fw-medium">{{ $component->product?->name }}</td>
                <td>{{ $component->quantity }}</td>
                <td>{{ $component->qty_consumed }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($order->workOrders->isNotEmpty())
        <x-table class="mt-3" :headers="['Work order', 'Center', 'Status', '']" title="Work orders">
            @foreach ($order->workOrders as $workOrder)
                <tr>
                    <td class="fw-medium">{{ $workOrder->name }}</td>
                    <td>{{ $workOrder->workCenter?->name ?: '—' }}</td>
                    <td><x-badge :tone="$workOrder->status === 'done' ? 'success' : 'warning'">{{ $workOrder->status }}</x-badge></td>
                    <td class="text-end">
                        @if (! $workOrder->isDone() && ($order->isDraft() || $order->isConfirmed()))
                            @can('manufacturing.orders.produce')
                                <form method="POST" action="{{ route('tenant.manufacturing-orders.work-orders.complete', [$order, $workOrder]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">Done</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-table>
    @endif

    @if ($order->operations->isNotEmpty())
        <x-table class="mt-3" :headers="['Operation', 'Type', 'Status']" title="Stock moves">
            @foreach ($order->operations as $operation)
                <tr>
                    <td class="font-monospace small">
                        <a href="{{ route('tenant.operations.show', $operation) }}">{{ $operation->number }}</a>
                    </td>
                    <td>{{ $operation->type }}</td>
                    <td>{{ $operation->status }}</td>
                </tr>
            @endforeach
        </x-table>
    @endif
@endsection
