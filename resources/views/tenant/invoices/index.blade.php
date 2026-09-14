@extends('layouts.app')

@section('title', 'Invoices')
@section('page-title', 'Invoices')
@section('page-subtitle', 'Finance')

@section('content')
    <p class="mb-3 text-secondary">Create from confirmed sales orders, then post and collect payment.</p>

    <x-table :headers="['Number', 'Customer', 'Status', 'Total', 'Due', '']">
        @forelse ($invoices as $invoice)
            <tr>
                <td class="font-monospace small">{{ $invoice->number }}</td>
                <td>{{ $invoice->customer?->name }}</td>
                <td>
                    @php
                        $tone = $invoice->isPaid() ? 'success' : ($invoice->isPosted() ? 'brand' : 'warning');
                        $label = $invoice->isPaid() ? 'paid' : $invoice->status;
                    @endphp
                    <x-badge :tone="$tone">{{ $label }}</x-badge>
                </td>
                <td>{{ $invoice->formattedGrandTotal() }}</td>
                <td>{{ $invoice->formattedAmountDue() }}</td>
                <td class="text-end">
                    <x-button href="{{ route('tenant.invoices.show', $invoice) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary">No invoices yet. Create one from a confirmed sales order.</td></tr>
        @endforelse
    </x-table>
@endsection
