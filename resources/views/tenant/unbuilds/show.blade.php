@extends('layouts.app')

@section('title', $order->number)
@section('page-title', 'Unbuild order')
@section('page-subtitle', $order->number)

@section('content')
    <div class="mb-3 d-flex flex-wrap justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <x-badge :tone="$order->status === 'done' ? 'success' : 'warning'">{{ $order->status }}</x-badge>
            <span class="fw-medium">{{ $order->product?->name }} × {{ $order->quantity }}</span>
            @if ($order->lot)
                <span class="text-secondary">Lot {{ $order->lot->name }}</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            @if ($order->isDraft())
                @can('manufacturing.unbuilds.validate')
                    <form method="POST" action="{{ route('tenant.unbuilds.validate', $order) }}" onsubmit="return confirm('Validate unbuild and return components?')">
                        @csrf
                        <x-button type="submit">Validate</x-button>
                    </form>
                @endcan
            @endif
            <x-button href="{{ route('tenant.unbuilds.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <x-table :headers="['Component', 'Qty returned (est.)']" title="BOM components">
        @foreach ($order->billOfMaterial?->lines ?? [] as $line)
            <tr>
                <td class="fw-medium">{{ $line->product?->name }}</td>
                <td>{{ (int) ceil(($line->quantity * $order->quantity) / max(1, $order->billOfMaterial->quantity)) }}</td>
            </tr>
        @endforeach
    </x-table>
@endsection
