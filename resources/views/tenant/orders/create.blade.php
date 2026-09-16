@extends('layouts.app')

@section('title', 'New sales order')
@section('page-title', 'New sales order')
@section('page-subtitle', 'Sales')

@section('content')
    <p class="mb-3 text-secondary">Lines, discount/tax, terms — Aureus-style document form.</p>

    @if ($customers->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-3">
            Add at least one customer and one active product before creating an order.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.orders.store') }}" class="vstack gap-3">
        @csrf

        <x-card>
            <div class="row">
                <div class="col-lg-6">
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
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-md-6">
                            <x-select label="Document type" name="kind" :searchable="false">
                                <option value="order" @selected(old('kind', 'order') === 'order')>Sales order</option>
                                <option value="quotation" @selected(old('kind') === 'quotation')>Quotation</option>
                            </x-select>
                        </div>
                        <div class="col-md-6">
                            <x-input label="Customer reference" name="client_order_ref" value="{{ old('client_order_ref') }}" />
                        </div>
                    </div>
                </div>
            </div>
        </x-card>

        <x-tabs :tabs="['lines' => 'Order lines', 'other' => 'Other info', 'terms' => 'Terms & conditions']">
            <x-tab-panel name="lines" :active="true">
                <x-document-lines :products="$products" :show-stock="true" />
            </x-tab-panel>
            <x-tab-panel name="other">
                <x-card>
                    <div class="row">
                        <div class="col-md-6">
                            <x-select label="Payment term" name="payment_term_id" placeholder="Search term…">
                                <option value="">No term</option>
                                @foreach ($paymentTerms as $term)
                                    <option value="{{ $term->id }}" @selected((string) old('payment_term_id') === (string) $term->id)>{{ $term->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-6">
                            <x-select label="Warehouse" name="warehouse_id" placeholder="Search warehouse…">
                                <option value="">Default warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <x-input label="Order date" name="ordered_at" type="date" value="{{ old('ordered_at') }}" />
                        </div>
                        <div class="col-md-4">
                            <x-input label="Quotation validity" name="validity_date" type="date" value="{{ old('validity_date') }}" />
                        </div>
                        <div class="col-md-4">
                            <x-input label="Delivery commitment" name="commitment_date" type="date" value="{{ old('commitment_date') }}" />
                        </div>
                    </div>
                    <x-textarea label="Notes" name="notes" rows="4" help="Internal or delivery notes.">{{ old('notes') }}</x-textarea>
                    @if ($taxes->isNotEmpty())
                        <div class="form-hint">Taxes on file: {{ $taxes->map(fn ($tax) => $tax->name)->implode(', ') }}</div>
                    @endif
                    @if ($uoms->isNotEmpty())
                        <div class="form-hint">Units on file: {{ $uoms->map(fn ($uom) => $uom->code)->implode(', ') }}</div>
                    @endif
                </x-card>
            </x-tab-panel>
            <x-tab-panel name="terms">
                <x-card>
                    <x-textarea label="Terms & conditions" name="terms" rows="6">{{ old('terms') }}</x-textarea>
                </x-card>
            </x-tab-panel>
        </x-tabs>

        <div class="d-flex flex-wrap gap-2">
            <x-button :disabled="$customers->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.orders.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
