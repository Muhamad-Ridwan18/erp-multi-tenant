@extends('layouts.app')

@section('title', $bill->number)
@section('page-title', 'Vendor bill')
@section('page-subtitle', $bill->number)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">{{ $bill->number }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                @php
                    $tone = $bill->isPaid() ? 'success' : ($bill->isPosted() ? 'brand' : 'warning');
                    $label = $bill->isPaid() ? 'paid' : $bill->status;
                @endphp
                <x-badge :tone="$tone">{{ $label }}</x-badge>
                <span class="text-sm text-ink-500">{{ $bill->vendor?->name }}</span>
                @if ($bill->purchaseOrder)
                    <a href="{{ route('tenant.purchases.show', $bill->purchaseOrder) }}" class="text-sm text-ink-600 underline">{{ $bill->purchaseOrder->number }}</a>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
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
            <x-button href="{{ route('tenant.bills.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Vendor</div>
            <div class="mt-2 font-semibold">{{ $bill->vendor?->name }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Total</div>
            <div class="mt-2 text-lg font-semibold">{{ $bill->formattedGrandTotal() }}</div>
            <div class="text-sm text-ink-500">Paid Rp {{ number_format($bill->amount_paid, 0, ',', '.') }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Amount due</div>
            <div class="mt-2 text-lg font-semibold">{{ $bill->formattedAmountDue() }}</div>
        </x-card>
    </div>

    <x-table class="mt-6" :headers="['Description', 'Qty', 'Unit price', 'Line total']">
        @foreach ($bill->items as $item)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $item->description }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $item->quantity }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($bill->isPosted() && ! $bill->isPaid())
        @can('finance.payments.create')
            <form method="POST" action="{{ route('tenant.bills.pay', $bill) }}" class="mt-6 max-w-md space-y-4">
                @csrf
                <x-card class="space-y-4">
                    <h2 class="font-medium text-ink-900">Record payment</h2>
                    <x-input label="Amount (Rp)" name="amount" type="number" min="1" max="{{ $bill->amountDue() }}" value="{{ old('amount', $bill->amountDue()) }}" required />
                    <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                    <x-button>Save payment</x-button>
                </x-card>
            </form>
        @endcan
    @endif

    @if ($bill->payments->isNotEmpty())
        <x-card class="mt-6" :padding="false">
            <div class="border-b border-line px-6 py-4 font-medium text-ink-900">Payments</div>
            <ul class="divide-y divide-line text-sm">
                @foreach ($bill->payments as $payment)
                    <li class="flex items-center justify-between px-6 py-3">
                        <span>
                            <span class="font-mono text-xs">{{ $payment->number }}</span>
                            <span class="text-ink-500">{{ $payment->paid_at?->format('d M Y H:i') }}</span>
                        </span>
                        <span class="font-medium text-red-700">{{ $payment->formattedAmount() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
@endsection
