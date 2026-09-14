@extends('layouts.app')

@section('title', $order->number)
@section('page-title', 'Purchase order')
@section('page-subtitle', $order->number)

@section('content')
    <x-progress-stepper
        :steps="['draft' => 'Draft', 'confirmed' => 'Confirmed', 'received' => 'Received', 'billed' => 'Billed', 'paid' => 'Paid']"
        :current="$order->progressStatus()"
    />

    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @php
                $tone = match ($order->status) {
                    'received' => 'success',
                    'confirmed' => 'brand',
                    default => 'warning',
                };
            @endphp
            <x-badge :tone="$tone">{{ $order->status }}</x-badge>
            <span class="text-secondary">{{ $order->vendor?->name }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
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

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Vendor</div>
                <div class="mt-2 fw-bold">{{ $order->vendor?->name }}</div>
                <div class="text-secondary small">{{ $order->vendor?->email }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Total</div>
                <div class="mt-2 fw-bold fs-4">{{ $order->formattedGrandTotal() }}</div>
                <div class="text-secondary small">Untaxed {{ $order->formattedSubtotal() }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Created by</div>
                <div class="mt-2 fw-bold">{{ $order->creator?->name ?? '—' }}</div>
                @if ($order->confirmed_at)
                    <div class="text-secondary small">Confirmed {{ $order->confirmed_at->format('d M Y H:i') }}</div>
                @endif
                @if ($order->received_at)
                    <div class="text-secondary small">Received {{ $order->received_at->format('d M Y H:i') }}</div>
                @endif
            </x-card>
        </div>
    </div>

    @if ($order->notes || $order->terms)
        <div class="row g-3 mt-0">
            @if ($order->notes)
                <div class="col-lg-6">
                    <x-card>
                        <div class="text-secondary text-uppercase small">Notes</div>
                        <p class="mt-2 mb-0" style="white-space: pre-line">{{ $order->notes }}</p>
                    </x-card>
                </div>
            @endif
            @if ($order->terms)
                <div class="col-lg-6">
                    <x-card>
                        <div class="text-secondary text-uppercase small">Terms</div>
                        <p class="mt-2 mb-0" style="white-space: pre-line">{{ $order->terms }}</p>
                    </x-card>
                </div>
            @endif
        </div>
    @endif

    <x-table class="mt-3" :headers="['Product', 'Qty', 'Price', 'Disc %', 'Tax %', 'Amount']">
        @foreach ($order->items as $item)
            <tr>
                <td class="fw-medium">{{ $item->product?->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>{{ $item->discount_percent }}%</td>
                <td>{{ $item->tax_percent }}%</td>
                <td>Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>

    <div class="mt-3 d-flex justify-content-end">
        <div style="min-width:16rem">
            <div class="d-flex justify-content-between gap-3 small"><span class="text-secondary">Discount</span><span>Rp {{ number_format($order->discount_total, 0, ',', '.') }}</span></div>
            <div class="d-flex justify-content-between gap-3 small"><span class="text-secondary">Tax</span><span>Rp {{ number_format($order->tax_total, 0, ',', '.') }}</span></div>
            <div class="d-flex justify-content-between gap-3 fw-bold"><span>Total</span><span>{{ $order->formattedGrandTotal() }}</span></div>
        </div>
    </div>
@endsection
