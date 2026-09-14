@extends('layouts.app')

@section('title', $order->number)
@section('page-title', 'Sales order')
@section('page-subtitle', $order->number)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">{{ $order->number }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-badge :tone="$order->status === 'confirmed' ? 'success' : 'warning'">{{ $order->status }}</x-badge>
                <span class="text-sm text-ink-500">{{ $order->customer?->name }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($order->isDraft())
                @can('sales.orders.confirm')
                    <form method="POST" action="{{ route('tenant.orders.confirm', $order) }}" onsubmit="return confirm('Confirm order and deduct stock?')">
                        @csrf
                        <x-button type="submit">Confirm order</x-button>
                    </form>
                @endcan
                @can('sales.orders.delete')
                    <form method="POST" action="{{ route('tenant.orders.destroy', $order) }}" onsubmit="return confirm('Delete draft?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">Delete</x-button>
                    </form>
                @endcan
            @endif
            @if ($order->isConfirmed())
                @can('finance.invoices.create')
                    @if ($order->invoice)
                        <x-button href="{{ route('tenant.invoices.show', $order->invoice) }}" variant="secondary">View invoice</x-button>
                    @else
                        <form method="POST" action="{{ route('tenant.invoices.from-order', $order) }}">
                            @csrf
                            <x-button type="submit">Create invoice</x-button>
                        </form>
                    @endif
                @endcan
            @endif
            <x-button href="{{ route('tenant.orders.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Customer</div>
            <div class="mt-2 font-semibold">{{ $order->customer?->name }}</div>
            <div class="text-sm text-ink-500">{{ $order->customer?->email }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Subtotal</div>
            <div class="mt-2 text-lg font-semibold">{{ $order->formattedSubtotal() }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Created by</div>
            <div class="mt-2 font-semibold">{{ $order->creator?->name ?? '—' }}</div>
            @if ($order->confirmed_at)
                <div class="text-sm text-ink-500">Confirmed {{ $order->confirmed_at->format('d M Y H:i') }}</div>
            @endif
        </x-card>
    </div>

    @if ($order->notes)
        <x-card class="mt-4">
            <div class="text-xs uppercase tracking-wide text-ink-500">Notes</div>
            <p class="mt-2 text-sm text-ink-700">{{ $order->notes }}</p>
        </x-card>
    @endif

    <x-table class="mt-6" :headers="['Product', 'Qty', 'Unit price', 'Line total']">
        @foreach ($order->items as $item)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $item->product?->name }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $item->quantity }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>
@endsection
