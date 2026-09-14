@extends('layouts.app')

@section('title', $invoice->number)
@section('page-title', 'Invoice')
@section('page-subtitle', $invoice->number)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">{{ $invoice->number }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                @php
                    $tone = $invoice->isPaid() ? 'success' : ($invoice->isPosted() ? 'brand' : 'warning');
                    $label = $invoice->isPaid() ? 'paid' : $invoice->status;
                @endphp
                <x-badge :tone="$tone">{{ $label }}</x-badge>
                <span class="text-sm text-ink-500">{{ $invoice->customer?->name }}</span>
                @if ($invoice->salesOrder)
                    <a href="{{ route('tenant.orders.show', $invoice->salesOrder) }}" class="text-sm text-ink-600 underline">{{ $invoice->salesOrder->number }}</a>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
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

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Customer</div>
            <div class="mt-2 font-semibold">{{ $invoice->customer?->name }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Total</div>
            <div class="mt-2 text-lg font-semibold">{{ $invoice->formattedGrandTotal() }}</div>
            <div class="text-sm text-ink-500">Paid Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-ink-500">Amount due</div>
            <div class="mt-2 text-lg font-semibold">{{ $invoice->formattedAmountDue() }}</div>
        </x-card>
    </div>

    <x-table class="mt-6" :headers="['Description', 'Qty', 'Unit price', 'Line total']">
        @foreach ($invoice->items as $item)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $item->description }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $item->quantity }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-ink-600">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($invoice->isPosted() && ! $invoice->isPaid())
        @can('finance.payments.create')
            <form method="POST" action="{{ route('tenant.invoices.pay', $invoice) }}" class="mt-6 max-w-md space-y-4">
                @csrf
                <x-card class="space-y-4">
                    <h2 class="font-medium text-ink-900">Record payment</h2>
                    <x-input label="Amount (Rp)" name="amount" type="number" min="1" max="{{ $invoice->amountDue() }}" value="{{ old('amount', $invoice->amountDue()) }}" required />
                    <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                    <x-button>Save payment</x-button>
                </x-card>
            </form>
        @endcan
    @endif

    @if ($invoice->payments->isNotEmpty())
        <x-card class="mt-6" :padding="false">
            <div class="border-b border-line px-6 py-4 font-medium text-ink-900">Payments</div>
            <ul class="divide-y divide-line text-sm">
                @foreach ($invoice->payments as $payment)
                    <li class="flex items-center justify-between px-6 py-3">
                        <span>
                            <span class="font-mono text-xs">{{ $payment->number }}</span>
                            <span class="text-ink-500">{{ $payment->paid_at?->format('d M Y H:i') }}</span>
                        </span>
                        <span class="font-medium text-emerald-700">{{ $payment->formattedAmount() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
@endsection
