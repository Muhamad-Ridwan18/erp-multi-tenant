@extends('layouts.app')

@section('title', 'New sales order')
@section('page-title', 'New sales order')
@section('page-subtitle', 'Sales')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">New sales order</h1>
            <p class="mt-1 text-sm text-ink-500">Header + order lines. Totals update as you type.</p>
        </div>
    </div>

    @if ($customers->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-4">
            Add at least one customer and one active product before creating an order.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.orders.store') }}" class="space-y-6">
        @csrf

        <x-card>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-select label="Customer" name="customer_id" required placeholder="Search customer…">
                    <option value="">Select customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                            {{ $customer->name }}@if ($customer->email) — {{ $customer->email }}@endif
                        </option>
                    @endforeach
                </x-select>
                <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
            </div>
        </x-card>

        <x-document-lines :products="$products" :show-stock="true" />

        <div class="flex flex-wrap gap-3">
            <x-button :disabled="$customers->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.orders.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
