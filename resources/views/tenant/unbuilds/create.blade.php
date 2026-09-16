@extends('layouts.app')

@section('title', 'Create unbuild')
@section('page-title', 'Create unbuild order')
@section('page-subtitle', 'Manufacturing')

@section('content')
    <form method="POST" action="{{ route('tenant.unbuilds.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <x-card>
                    <x-select label="Finished product" name="product_id" required>
                        <option value="">Select…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="BOM" name="bill_of_material_id">
                        <option value="">Latest active BOM</option>
                        @foreach ($boms as $bom)
                            <option value="{{ $bom->id }}">{{ $bom->code ?: '#'.$bom->id }} — {{ $bom->product?->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Lot (optional)" name="lot_id">
                        <option value="">None</option>
                        @foreach ($lots as $lot)
                            <option value="{{ $lot->id }}">{{ $lot->name }} — {{ $lot->product?->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required />
                    <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
                </x-card>
            </div>
            <div class="col-lg-4">
                <x-card>
                    <x-button class="w-100">Create draft</x-button>
                    <a href="{{ route('tenant.unbuilds.index') }}" class="btn btn-ghost-secondary w-100 mt-2">Cancel</a>
                </x-card>
            </div>
        </div>
    </form>
@endsection
