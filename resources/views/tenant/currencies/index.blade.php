@extends('layouts.app')

@section('title', 'Currencies')
@section('page-title', 'Currencies')
@section('page-subtitle', 'Finance')

@section('content')
    <p class="text-secondary mb-3">
        Rate = company currency (IDR) units per 1 foreign unit. Example: USD rate 16000 means 1 USD = Rp 16.000.
    </p>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-table :headers="['Code', 'Name', 'Symbol', 'Rate', 'Status', '']" title="Exchange rates">
                @forelse ($currencies as $currency)
                    <tr>
                        <td class="font-monospace small fw-bold">{{ $currency->code }}</td>
                        <td>{{ $currency->name }}</td>
                        <td>{{ $currency->symbol }}</td>
                        <td class="font-monospace">{{ rtrim(rtrim(number_format((float) $currency->rate, 6, '.', ''), '0'), '.') }}</td>
                        <td>
                            <x-badge :tone="$currency->is_active ? 'success' : 'neutral'">
                                {{ $currency->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="text-end">
                            @can('finance.currencies.manage')
                                <form method="POST" action="{{ route('tenant.currencies.update', $currency) }}" class="d-inline-flex flex-wrap gap-1 align-items-center justify-content-end">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $currency->name }}">
                                    <input type="hidden" name="symbol" value="{{ $currency->symbol }}">
                                    <input type="hidden" name="is_active" value="1">
                                    <input
                                        type="number"
                                        name="rate"
                                        class="form-control form-control-sm"
                                        style="width: 8rem"
                                        step="0.000001"
                                        min="0.000001"
                                        value="{{ $currency->rate }}"
                                        @disabled($currency->code === 'IDR')
                                        required
                                    >
                                    <button class="btn btn-sm btn-primary" @disabled($currency->code === 'IDR')>Update</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No currencies configured.</td></tr>
                @endforelse
            </x-table>
        </div>

        @can('finance.currencies.manage')
            <div class="col-lg-4">
                <form method="POST" action="{{ route('tenant.currencies.store') }}">
                    @csrf
                    <x-card>
                        <h2 class="h3 mb-3">Add currency</h2>
                        <x-input label="Code" name="code" value="{{ old('code') }}" required help="e.g. USD" />
                        <x-input label="Name" name="name" value="{{ old('name') }}" required />
                        <x-input label="Symbol" name="symbol" value="{{ old('symbol') }}" />
                        <x-input label="Rate to IDR" name="rate" type="number" step="0.000001" min="0.000001" value="{{ old('rate') }}" required />
                        <x-button>Save</x-button>
                    </x-card>
                </form>
            </div>
        @endcan
    </div>
@endsection
