@extends('layouts.app')

@section('title', 'Create product')
@section('page-title', 'Create product')
@section('page-subtitle', 'Inventory')

@section('content')
    <p class="mb-3 text-secondary">Catalog identity on the left, pricing & stock on the right.</p>

    <form method="POST" action="{{ route('tenant.products.store') }}" class="vstack gap-3">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus class="fs-4 fw-medium" />
                    <x-input label="SKU" name="sku" value="{{ old('sku') }}" required help="Unique product code" />
                    <x-textarea label="Description" name="description" rows="4">{{ old('description') }}</x-textarea>
                </x-card>
            </div>

            <div class="col-lg-4">
                <x-card>
                    <x-input label="Unit" name="unit" value="{{ old('unit', 'pcs') }}" required />
                    <x-input label="Sales price (Rp)" name="price" type="number" min="0" value="{{ old('price', 0) }}" required />
                    <x-input label="Opening stock" name="stock_qty" type="number" min="0" value="{{ old('stock_qty', 0) }}" required />
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-button>Create</x-button>
            <x-button href="{{ route('tenant.products.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
