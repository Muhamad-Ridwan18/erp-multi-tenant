@extends('layouts.app')

@section('title', 'New invoice')
@section('page-title', 'New invoice')
@section('page-subtitle', 'Finance')

@section('content')
    <p class="mb-3 text-secondary">Customer invoice without a sales order — lines, dates and journal.</p>

    @if ($customers->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-3">
            Add at least one customer and one active product before creating an invoice.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.invoices.store') }}" class="vstack gap-3">
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
                    <x-input label="Reference" name="reference" value="{{ old('reference') }}" help="Customer reference or memo" />
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-md-6">
                            <x-input label="Invoice date" name="invoice_date" type="date" value="{{ old('invoice_date', now()->toDateString()) }}" />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Due date" name="due_date" type="date" value="{{ old('due_date') }}" help="Derived from the payment term when empty" />
                        </div>
                    </div>
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
                            <x-select label="Journal" name="journal_id" placeholder="Search journal…">
                                <option value="">Default sales journal</option>
                                @foreach ($journals as $journal)
                                    <option value="{{ $journal->id }}" @selected((string) old('journal_id') === (string) $journal->id)>{{ $journal->code }} — {{ $journal->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-select label="Currency" name="currency_id" placeholder="Search currency…">
                                <option value="">IDR (company)</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected((string) old('currency_id') === (string) $currency->id)>
                                        {{ $currency->code }} — {{ $currency->name }} (rate {{ rtrim(rtrim(number_format((float) $currency->rate, 6, '.', ''), '0'), '.') }})
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>

        <div>
            <x-document-lines :products="$products" />
            @if ($taxes->isNotEmpty())
                <div class="form-hint mt-2">Taxes on file: {{ $taxes->map(fn ($tax) => $tax->name)->implode(', ') }}</div>
            @endif
        </div>

        <x-card>
            <x-textarea label="Notes" name="notes" rows="3">{{ old('notes') }}</x-textarea>
            <x-textarea label="Terms & conditions" name="terms" rows="3">{{ old('terms') }}</x-textarea>
        </x-card>

        <div class="d-flex flex-wrap gap-2">
            <x-button :disabled="$customers->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.invoices.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
