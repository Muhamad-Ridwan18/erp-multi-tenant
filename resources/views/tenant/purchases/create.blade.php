@extends('layouts.app')

@section('title', 'New purchase order')
@section('page-title', 'New purchase order')
@section('page-subtitle', 'Procurement')

@section('content')
    <p class="mb-3 text-secondary">Vendor, lines with discount/tax, and terms.</p>

    @if ($vendors->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-3">
            Add at least one vendor and one active product before creating a purchase order.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.purchases.store') }}" class="vstack gap-3">
        @csrf

        <x-card>
            <div>
                <x-select id="vendor_id" label="Vendor" name="vendor_id" required placeholder="Search vendor…">
                    <option value="">Select vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                            {{ $vendor->name }}@if ($vendor->email) — {{ $vendor->email }}@endif
                        </option>
                    @endforeach
                </x-select>
                <x-quick-create-trigger
                    type="vendor"
                    select-id="vendor_id"
                    :store-url="route('tenant.vendors.quick')"
                    :can-create="auth()->user()->can('procurement.vendors.create')"
                />
            </div>
        </x-card>

        <x-tabs :tabs="['lines' => 'Order lines', 'other' => 'Other info', 'terms' => 'Terms & conditions']">
            <x-tab-panel name="lines" :active="true">
                <x-document-lines :products="$products" />
            </x-tab-panel>
            <x-tab-panel name="other">
                <x-card>
                    <x-textarea label="Notes" name="notes" rows="4">{{ old('notes') }}</x-textarea>
                </x-card>
            </x-tab-panel>
            <x-tab-panel name="terms">
                <x-card>
                    <x-textarea label="Terms & conditions" name="terms" rows="6">{{ old('terms') }}</x-textarea>
                </x-card>
            </x-tab-panel>
        </x-tabs>

        <div class="d-flex flex-wrap gap-2">
            <x-button :disabled="$vendors->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.purchases.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
