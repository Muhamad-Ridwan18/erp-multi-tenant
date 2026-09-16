@extends('layouts.app')

@section('title', 'Invoices')
@section('page-title', 'Invoices')
@section('page-subtitle', 'Finance')

@section('page-actions')
    @can('finance.invoices.create')
        <a href="{{ route('tenant.invoices.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New invoice
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Number', 'Customer', 'Status', 'Total', 'Due', '']" title="Invoices">
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
                    <a href="{{ route('tenant.invoices.show', $invoice) }}" class="btn btn-ghost-primary btn-sm">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">No invoices yet. Create one from a confirmed sales order.</td></tr>
        @endforelse
    </x-table>
@endsection
