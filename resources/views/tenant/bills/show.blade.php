@extends('layouts.app')

@section('title', $bill->number)
@section('page-title', 'Vendor bill')
@section('page-subtitle', $bill->number)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @php
                $tone = $bill->isPaid() ? 'success' : ($bill->isPosted() ? 'brand' : 'warning');
                $label = $bill->isPaid() ? 'paid' : $bill->status;
            @endphp
            <x-badge :tone="$tone">{{ $label }}</x-badge>
            <span class="text-secondary">{{ $bill->vendor?->name }}</span>
            @if ($bill->purchaseOrder)
                <a href="{{ route('tenant.purchases.show', $bill->purchaseOrder) }}" class="small">{{ $bill->purchaseOrder->number }}</a>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($bill->isDraft())
                @can('finance.bills.post')
                    <form method="POST" action="{{ route('tenant.bills.post', $bill) }}" onsubmit="return confirm('Post this bill?')">
                        @csrf
                        <x-button type="submit">Post bill</x-button>
                    </form>
                @endcan
                @can('finance.bills.delete')
                    <form method="POST" action="{{ route('tenant.bills.destroy', $bill) }}" onsubmit="return confirm('Delete draft?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">Delete</x-button>
                    </form>
                @endcan
            @endif
            @if ($bill->isPosted() && ! $bill->isRefund() && ! $bill->isReversed())
                @can('finance.bills.create')
                    <form method="POST" action="{{ route('tenant.bills.refund', $bill) }}" onsubmit="return confirm('Create and post a vendor refund for this bill?')">
                        @csrf
                        <x-button type="submit" variant="ghost">Refund</x-button>
                    </form>
                @endcan
            @endif
            <x-button href="{{ route('tenant.bills.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Vendor</div>
                <div class="mt-2 fw-bold">{{ $bill->vendor?->name }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Total</div>
                <div class="mt-2 fw-bold fs-4">{{ $bill->formattedGrandTotal() }}</div>
                <div class="text-secondary small">Paid Rp {{ number_format($bill->amount_paid, 0, ',', '.') }}</div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Amount due</div>
                <div class="mt-2 fw-bold fs-4">{{ $bill->formattedAmountDue() }}</div>
            </x-card>
        </div>
    </div>

    <x-table class="mt-3" :headers="['Description', 'Qty', 'Unit price', 'Line total']">
        @foreach ($bill->items as $item)
            <tr>
                <td class="fw-medium">{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($bill->isPosted() && ! $bill->isPaid())
        @can('finance.payments.create')
            <form method="POST" action="{{ route('tenant.bills.pay', $bill) }}" class="mt-3">
                @csrf
                <div class="row">
                    <div class="col-lg-4">
                        <x-card>
                            <h2 class="h3 mb-3">Record payment</h2>
                            <x-input label="Amount (Rp)" name="amount" type="number" min="1" max="{{ $bill->amountDue() }}" value="{{ old('amount', $bill->amountDue()) }}" required />
                            <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                            <x-button>Save payment</x-button>
                        </x-card>
                    </div>
                </div>
            </form>
        @endcan
    @endif

    @if ($bill->payments->isNotEmpty())
        <x-card class="mt-3" :padding="false">
            <div class="card-header fw-medium">Payments</div>
            <ul class="list-group list-group-flush">
                @foreach ($bill->payments as $payment)
                    <li class="list-group-item d-flex align-items-center justify-content-between">
                        <span>
                            <span class="font-monospace small">{{ $payment->number }}</span>
                            <span class="text-secondary">{{ $payment->paid_at?->format('d M Y H:i') }}</span>
                        </span>
                        <span class="fw-medium text-danger">{{ $payment->formattedAmount() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
@endsection
