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
                    <div class="row">
                        <div class="col-md-6">
                            <x-input label="SKU" name="sku" value="{{ old('sku') }}" required help="Unique product code" />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Barcode" name="barcode" value="{{ old('barcode') }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-select label="Type" name="type" :searchable="false">
                                <option value="goods" @selected(old('type', 'goods') === 'goods')>Goods</option>
                                <option value="service" @selected(old('type') === 'service')>Service</option>
                            </x-select>
                        </div>
                        <div class="col-md-6">
                            <x-select label="Tracking" name="tracking" :searchable="false">
                                <option value="none" @selected(old('tracking', 'none') === 'none')>No tracking</option>
                                <option value="lot" @selected(old('tracking') === 'lot')>By lots</option>
                                <option value="serial" @selected(old('tracking') === 'serial')>By unique serial</option>
                            </x-select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-select label="Category" name="product_category_id" placeholder="Search category…">
                                <option value="">No category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('product_category_id') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <x-textarea label="Description" name="description" rows="4">{{ old('description') }}</x-textarea>
                </x-card>
            </div>

            <div class="col-lg-4">
                <x-card>
                    <div class="row">
                        <div class="col-md-6">
                            <x-select label="Sales UoM" name="uom_id" placeholder="Search unit…">
                                <option value="">Default</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) old('uom_id') === (string) $uom->id)>{{ $uom->name }} ({{ $uom->code }})</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-6">
                            <x-select label="Purchase UoM" name="purchase_uom_id" placeholder="Search unit…">
                                <option value="">Default</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) old('purchase_uom_id') === (string) $uom->id)>{{ $uom->name }} ({{ $uom->code }})</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <x-input label="Unit label" name="unit" value="{{ old('unit', 'pcs') }}" help="Shown on documents" />
                    <div class="row">
                        <div class="col-md-6">
                            <x-input label="Sales price (Rp)" name="price" type="number" min="0" value="{{ old('price', 0) }}" required />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Cost (Rp)" name="cost" type="number" min="0" value="{{ old('cost', 0) }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-input label="Weight (kg)" name="weight" type="number" step="0.001" min="0" value="{{ old('weight') }}" />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Volume (m³)" name="volume" type="number" step="0.001" min="0" value="{{ old('volume') }}" />
                        </div>
                    </div>
                    <x-input label="Opening stock" name="stock_qty" type="number" min="0" value="{{ old('stock_qty', 0) }}" required help="Posted to the stock location as an adjustment" />
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
