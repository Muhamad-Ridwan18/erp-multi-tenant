@extends('layouts.app')

@section('title', 'Invoices')
@section('page-title', 'Invoices')
@section('page-subtitle', 'Finance')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">Customer invoices</h1>
        <p class="mt-1 text-sm text-ink-500">Create from confirmed sales orders, then post and collect payment.</p>
    </div>

    <x-table :headers="['Number', 'Customer', 'Status', 'Total', 'Due', '']">
        @forelse ($invoices as $invoice)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-ink-800">{{ $invoice->number }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $invoice->customer?->name }}</td>
                <td class="px-4 py-3">
                    @php
                        $tone = $invoice->isPaid() ? 'success' : ($invoice->isPosted() ? 'brand' : 'warning');
                        $label = $invoice->isPaid() ? 'paid' : $invoice->status;
                    @endphp
                    <x-badge :tone="$tone">{{ $label }}</x-badge>
                </td>
                <td class="px-4 py-3 text-ink-700">{{ $invoice->formattedSubtotal() }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $invoice->formattedAmountDue() }}</td>
                <td class="px-4 py-3 text-right">
                    <x-button href="{{ route('tenant.invoices.show', $invoice) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-ink-500">No invoices yet. Create one from a confirmed sales order.</td></tr>
        @endforelse
    </x-table>
@endsection
