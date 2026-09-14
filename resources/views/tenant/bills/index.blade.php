@extends('layouts.app')

@section('title', 'Bills')
@section('page-title', 'Vendor bills')
@section('page-subtitle', 'Finance')

@section('content')
    <x-table :headers="['Number', 'Vendor', 'Status', 'Total', 'Due', '']" title="Bills">
        @forelse ($bills as $bill)
            <tr>
                <td class="font-monospace small">{{ $bill->number }}</td>
                <td>{{ $bill->vendor?->name }}</td>
                <td>
                    @php
                        $tone = $bill->isPaid() ? 'success' : ($bill->isPosted() ? 'brand' : 'warning');
                        $label = $bill->isPaid() ? 'paid' : $bill->status;
                    @endphp
                    <x-badge :tone="$tone">{{ $label }}</x-badge>
                </td>
                <td>{{ $bill->formattedGrandTotal() }}</td>
                <td>{{ $bill->formattedAmountDue() }}</td>
                <td class="text-end">
                    <a href="{{ route('tenant.bills.show', $bill) }}" class="btn btn-ghost-primary btn-sm">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">No bills yet. Create one from a received purchase order.</td></tr>
        @endforelse
    </x-table>
@endsection
