@extends('layouts.app')

@section('title', $order->number)
@section('page-title', 'Purchase order')
@section('page-subtitle', $order->number)

@section('content')
    <x-progress-stepper
        :steps="['draft' => 'Draft', 'confirmed' => 'Confirmed', 'received' => 'Received', 'billed' => 'Billed', 'paid' => 'Paid']"
        :current="$order->progressStatus()"
    />

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">{{ $order->number }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                @php
                    $tone = match ($order->status) {
                        'received' => 'success',
                        'confirmed' => 'brand',
                        default => 'warning',
                    };
                @endphp
                <x-badge :tone="$tone">{{ $order->status }}</x-badge>
                <span class="text-sm text-ink-500">{{ $order->vendor?->name }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($order->isDraft())
                @can('procurement.orders.confirm')
                    <form method="POST" action="{{ route('tenant.purchases.confirm', $order) }}" onsubmit="return confirm('Confirm this purchase order?')">
                        @csrf
                        <x-button type="submit">Confirm PO</x-button>
                    </form>
                @endcan
                @can('procurement.orders.delete')
                    <form method="POST" action="{{ route('tenant.purchases.destroy', $order) }}" onsubmit="return confirm('Delete draft?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">Delete</x-button>
                    </form>
                @endcan
            @endif
            @if ($order->isConfirmed())
                @can('procurement.receipts.receive')
                    <form method="POST" action="{{ route('tenant.purchases.receive', $order) }}" onsubmit="return confirm('Receive goods and add stock?')">
                        @csrf
                        <x-button type="submit">Receive goods</x-button>
                    </form>
                @endcan
            @endif
            @if ($order->isReceived())
                @can('finance.bills.create')
                    @if ($order->bill)
                        <x-button href="{{ route('tenant.bills.show', $order->bill) }}" variant="secondary">View bill</x-button>
                    @else
                        <form method="POST" action="{{ route('tenant.bills.from-purchase', $order) }}">
                            @csrf
                            <x-button type="submit">Create bill</x-button>
                        </form>
                    @endif
                @endcan
            @endif
            <x-button href="{{ route('tenant.purchases.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Vendor</div>
            <div class="mt-2 font-semibold">{{ $order->vendor?->name }}</div>
            <div class="text-sm text-ink-500">{{ $order->vendor?->email }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Total</div>
            <div class="mt-2 text-lg font-semibold">{{ $order->formattedGrandTotal() }}</div>
            <div class="text-sm text-ink-500">Untaxed {{ $order->formattedSubtotal() }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Created by</div>
            <div class="mt-2 font-semibold">{{ $order->creator?->name ?? '—' }}</div>
            @if ($order->confirmed_at)
                <div class="text-sm text-ink-500">Confirmed {{ $order->confirmed_at->format('d M Y H:i') }}</div>
            @endif
            @if ($order->received_at)
                <div class="text-sm text-ink-500">Received {{ $order->received_at->format('d M Y H:i') }}</div>
            @endif
        </x-card>
    </div>

    @if ($order->notes || $order->terms)
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @if ($order->notes)
                <x-card>
                    <div class="text-xs uppercase tracking-wide text-ink-500">Notes</div>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $order->notes }}</p>
                </x-card>
            @endif
            @if ($order->terms)
                <x-card>
                    <div class="text-xs uppercase tracking-wide text-ink-500">Terms</div>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $order->terms }}</p>
                </x-card>
            @endif
        </div>
    @endif

    <x-table class="mt-6" :headers="['Product', 'Qty', 'Price', 'Disc %', 'Tax %', 'Amount']">
        @foreach ($order->items as $item)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $item->product?->name }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $item->quantity }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $item->discount_percent }}%</td>
                <td class="px-4 py-3 text-ink-600">{{ $item->tax_percent }}%</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>

    <div class="mt-4 space-y-1 text-sm">
        <div class="flex justify-end gap-8"><span class="text-ink-500">Discount</span><span class="min-w-[8rem] text-right">Rp {{ number_format($order->discount_total, 0, ',', '.') }}</span></div>
        <div class="flex justify-end gap-8"><span class="text-ink-500">Tax</span><span class="min-w-[8rem] text-right">Rp {{ number_format($order->tax_total, 0, ',', '.') }}</span></div>
        <div class="flex justify-end gap-8 font-semibold"><span>Total</span><span class="min-w-[8rem] text-right">{{ $order->formattedGrandTotal() }}</span></div>
    </div>
@endsection
