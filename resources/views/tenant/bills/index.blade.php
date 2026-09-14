@extends('layouts.app')

@section('title', 'Bills')
@section('page-title', 'Vendor bills')
@section('page-subtitle', 'Finance')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">Vendor bills</h1>
        <p class="mt-1 text-sm text-ink-500">Create from received purchase orders, then post and pay.</p>
    </div>

    <x-table :headers="['Number', 'Vendor', 'Status', 'Total', 'Due', '']">
        @forelse ($bills as $bill)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-ink-800">{{ $bill->number }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $bill->vendor?->name }}</td>
                <td class="px-4 py-3">
                    @php
                        $tone = $bill->isPaid() ? 'success' : ($bill->isPosted() ? 'brand' : 'warning');
                        $label = $bill->isPaid() ? 'paid' : $bill->status;
                    @endphp
                    <x-badge :tone="$tone">{{ $label }}</x-badge>
                </td>
                <td class="px-4 py-3 text-ink-700">{{ $bill->formattedSubtotal() }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $bill->formattedAmountDue() }}</td>
                <td class="px-4 py-3 text-right">
                    <x-button href="{{ route('tenant.bills.show', $bill) }}" variant="ghost">View</x-button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-ink-500">No bills yet. Create one from a received purchase order.</td></tr>
        @endforelse
    </x-table>
@endsection
