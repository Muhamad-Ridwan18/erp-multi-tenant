@extends('layouts.app')

@section('title', 'New sales order')
@section('page-title', 'New sales order')
@section('page-subtitle', 'Sales')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">New sales order</h1>
        <p class="mt-1 text-sm text-ink-500">Lines, discount/tax, terms — Aureus-style document form.</p>
    </div>

    @if ($customers->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-4">
            Add at least one customer and one active product before creating an order.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.orders.store') }}" class="space-y-6">
        @csrf

        <x-card>
            <div>
                <x-select id="customer_id" label="Customer" name="customer_id" required placeholder="Search customer…">
                    <option value="">Select customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                            {{ $customer->name }}@if ($customer->email) — {{ $customer->email }}@endif
                        </option>
                    @endforeach
                </x-select>
                <x-quick-create-trigger
                    type="customer"
                    select-id="customer_id"
                    :store-url="route('tenant.customers.quick')"
                    :can-create="auth()->user()->can('sales.customers.create')"
                />
            </div>
        </x-card>

        <x-tabs :tabs="['lines' => 'Order lines', 'other' => 'Other info', 'terms' => 'Terms & conditions']">
            <x-tab-panel name="lines" :active="true">
                <x-document-lines :products="$products" :show-stock="true" />
            </x-tab-panel>
            <x-tab-panel name="other">
                <x-card>
                    <x-textarea label="Notes" name="notes" rows="4" help="Internal or delivery notes.">{{ old('notes') }}</x-textarea>
                </x-card>
            </x-tab-panel>
            <x-tab-panel name="terms">
                <x-card>
                    <x-textarea label="Terms & conditions" name="terms" rows="6">{{ old('terms') }}</x-textarea>
                </x-card>
            </x-tab-panel>
        </x-tabs>

        <div class="flex flex-wrap gap-3">
            <x-button :disabled="$customers->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.orders.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
