@extends('layouts.app')

@section('title', 'New bill')
@section('page-title', 'New bill')
@section('page-subtitle', 'Finance')

@section('content')
    <p class="mb-3 text-secondary">Vendor bill without a purchase order — lines, dates and journal.</p>

    @if ($vendors->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-3">
            Add at least one vendor and one active product before creating a bill.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.bills.store') }}" class="vstack gap-3">
        @csrf

        <x-card>
            <div class="row">
                <div class="col-lg-6">
                    <x-select id="vendor_id" label="Vendor" name="vendor_id" required placeholder="Search vendor…">
                        <option value="">Select vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                                {{ $vendor->name }}@if ($vendor->email) — {{ $vendor->email }}@endif
                            </option>
                        @endforeach
                    </x-select>
                    <x-input label="Vendor reference" name="reference" value="{{ old('reference') }}" help="Number printed on the vendor document" />
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-md-6">
                            <x-input label="Bill date" name="bill_date" type="date" value="{{ old('bill_date', now()->toDateString()) }}" />
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
                                <option value="">Default purchase journal</option>
                                @foreach ($journals as $journal)
                                    <option value="{{ $journal->id }}" @selected((string) old('journal_id') === (string) $journal->id)>{{ $journal->code }} — {{ $journal->name }}</option>
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
            <x-button :disabled="$vendors->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.bills.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
