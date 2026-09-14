@extends('layouts.app')

@section('title', $invoice->number)
@section('page-title', 'Invoice')
@section('page-subtitle', $invoice->number)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @php
                $tone = $invoice->isPaid() ? 'success' : ($invoice->isPosted() ? 'brand' : 'warning');
                $label = $invoice->isPaid() ? 'paid' : $invoice->status;
            @endphp
            <x-badge :tone="$tone">{{ $label }}</x-badge>
            <span class="text-secondary">{{ $invoice->customer?->name }}</span>
            @if ($invoice->salesOrder)
                <a href="{{ route('tenant.orders.show', $invoice->salesOrder) }}" class="small">{{ $invoice->salesOrder->number }}</a>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($invoice->isDraft())
                @can('finance.invoices.post')
                    <form method="POST" action="{{ route('tenant.invoices.post', $invoice) }}" onsubmit="return confirm('Post this invoice?')">
                        @csrf
                        <x-button type="submit">Post invoice</x-button>
                    </form>
                @endcan
                @can('finance.invoices.delete')
                    <form method="POST" action="{{ route('tenant.invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete draft?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">Delete</x-button>
                    </form>
                @endcan
            @endif
            <x-button href="{{ route('tenant.invoices.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Customer</div>
                <div class="mt-2 fw-bold">{{ $invoice->customer?->name }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Total</div>
                <div class="mt-2 fw-bold fs-4">{{ $invoice->formattedGrandTotal() }}</div>
                <div class="text-secondary small">Paid Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Amount due</div>
                <div class="mt-2 fw-bold fs-4">{{ $invoice->formattedAmountDue() }}</div>
            </x-card>
        </div>
    </div>

    <x-table class="mt-3" :headers="['Description', 'Qty', 'Unit price', 'Line total']">
        @foreach ($invoice->items as $item)
            <tr>
                <td class="fw-medium">{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($invoice->isPosted() && ! $invoice->isPaid())
        @can('finance.payments.create')
            <form method="POST" action="{{ route('tenant.invoices.pay', $invoice) }}" class="mt-3">
                @csrf
                <div class="row">
                    <div class="col-lg-4">
                        <x-card>
                            <h2 class="h3 mb-3">Record payment</h2>
                            <x-input label="Amount (Rp)" name="amount" type="number" min="1" max="{{ $invoice->amountDue() }}" value="{{ old('amount', $invoice->amountDue()) }}" required />
                            <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                            <x-button>Save payment</x-button>
                        </x-card>
                    </div>
                </div>
            </form>
        @endcan
    @endif

    @if ($invoice->payments->isNotEmpty())
        <x-card class="mt-3" :padding="false">
            <div class="card-header fw-medium">Payments</div>
            <ul class="list-group list-group-flush">
                @foreach ($invoice->payments as $payment)
                    <li class="list-group-item d-flex align-items-center justify-content-between">
                        <span>
                            <span class="font-monospace small">{{ $payment->number }}</span>
                            <span class="text-secondary">{{ $payment->paid_at?->format('d M Y H:i') }}</span>
                        </span>
                        <span class="fw-medium text-success">{{ $payment->formattedAmount() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
@endsection
