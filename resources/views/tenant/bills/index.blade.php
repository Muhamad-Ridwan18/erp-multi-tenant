@extends('layouts.app')

@section('title', 'Bills')
@section('page-title', 'Vendor bills')
@section('page-subtitle', 'Finance')

@section('content')
    <p class="mb-3 text-secondary">Create from received purchase orders, then post and pay.</p>

    <x-table :headers="['Number', 'Vendor', 'Status', 'Total', 'Due', '']">
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
                    <x-button href="{{ route('tenant.bills.show', $bill) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary">No bills yet. Create one from a received purchase order.</td></tr>
        @endforelse
    </x-table>
@endsection
