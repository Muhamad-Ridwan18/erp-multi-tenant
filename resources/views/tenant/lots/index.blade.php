@extends('layouts.app')

@section('title', 'Lots')
@section('page-title', 'Lots / serials')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-table :headers="['Lot', 'Product', 'Reference', 'Expiry']" title="Lots">
                @forelse ($lots as $lot)
                    <tr>
                        <td class="font-monospace small fw-medium">{{ $lot->name }}</td>
                        <td>{{ $lot->product?->name }}</td>
                        <td class="text-secondary">{{ $lot->reference ?: '—' }}</td>
                        <td class="text-secondary">{{ $lot->expiration_date?->format('d M Y') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">No lots yet.</td></tr>
                @endforelse
            </x-table>
        </div>
        @can('inventory.lots.manage')
            <div class="col-lg-4">
                <form method="POST" action="{{ route('tenant.lots.store') }}">
                    @csrf
                    <x-card>
                        <h2 class="h3 mb-3">New lot</h2>
                        <x-select label="Product" name="product_id" required>
                            <option value="">Select tracked product…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->tracking }})</option>
                            @endforeach
                        </x-select>
                        <x-input label="Lot / serial name" name="name" value="{{ old('name') }}" required />
                        <x-input label="Reference" name="reference" value="{{ old('reference') }}" />
                        <x-input label="Expiration" name="expiration_date" type="date" value="{{ old('expiration_date') }}" />
                        <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
                        <x-button>Save</x-button>
                    </x-card>
                </form>
            </div>
        @endcan
    </div>
@endsection
