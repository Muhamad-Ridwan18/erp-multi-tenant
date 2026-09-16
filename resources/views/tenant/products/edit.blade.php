@extends('layouts.app')

@section('title', 'Edit product')
@section('page-title', 'Edit product')
@section('page-subtitle', $product->sku)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <p class="mb-0 text-secondary">{{ $product->name }} — stock on hand: <strong>{{ $product->stock_qty }} {{ $product->unit }}</strong></p>
        @can('inventory.products.delete')
            <form method="POST" action="{{ route('tenant.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('tenant.products.update', $product) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-8">
                        <x-card>
                            <x-input label="Name" name="name" value="{{ old('name', $product->name) }}" required class="fs-4 fw-medium" />
                            <div class="row">
                                <div class="col-md-6">
                                    <x-input label="SKU" name="sku" value="{{ old('sku', $product->sku) }}" required />
                                </div>
                                <div class="col-md-6">
                                    <x-input label="Barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-select label="Type" name="type" :searchable="false">
                                        <option value="goods" @selected(old('type', $product->type ?? 'goods') === 'goods')>Goods</option>
                                        <option value="service" @selected(old('type', $product->type) === 'service')>Service</option>
                                    </x-select>
                                </div>
                                <div class="col-md-6">
                                    <x-select label="Tracking" name="tracking" :searchable="false">
                                        <option value="none" @selected(old('tracking', $product->tracking ?? 'none') === 'none')>No tracking</option>
                                        <option value="lot" @selected(old('tracking', $product->tracking) === 'lot')>By lots</option>
                                        <option value="serial" @selected(old('tracking', $product->tracking) === 'serial')>By unique serial</option>
                                    </x-select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-select label="Category" name="product_category_id" placeholder="Search category…">
                                        <option value="">No category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected((string) old('product_category_id', $product->product_category_id) === (string) $category->id)>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                            <x-textarea label="Description" name="description" rows="4">{{ old('description', $product->description) }}</x-textarea>
                        </x-card>
                    </div>
                    <div class="col-md-4">
                        <x-card>
                            <x-select label="Sales UoM" name="uom_id" placeholder="Search unit…">
                                <option value="">Default</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) old('uom_id', $product->uom_id) === (string) $uom->id)>{{ $uom->name }} ({{ $uom->code }})</option>
                                @endforeach
                            </x-select>
                            <x-select label="Purchase UoM" name="purchase_uom_id" placeholder="Search unit…">
                                <option value="">Default</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) old('purchase_uom_id', $product->purchase_uom_id) === (string) $uom->id)>{{ $uom->name }} ({{ $uom->code }})</option>
                                @endforeach
                            </x-select>
                            <x-input label="Unit label" name="unit" value="{{ old('unit', $product->unit) }}" />
                            <div class="row">
                                <div class="col-md-6">
                                    <x-input label="Sales price (Rp)" name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required />
                                </div>
                                <div class="col-md-6">
                                    <x-input label="Cost (Rp)" name="cost" type="number" min="0" value="{{ old('cost', $product->cost) }}" />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-input label="Weight (kg)" name="weight" type="number" step="0.001" min="0" value="{{ old('weight', $product->weight) }}" />
                                </div>
                                <div class="col-md-6">
                                    <x-input label="Volume (m³)" name="volume" type="number" step="0.001" min="0" value="{{ old('volume', $product->volume) }}" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $product->is_active))>
                                    <span class="form-check-label">Active</span>
                                </label>
                            </div>
                            <x-button>Save product</x-button>
                        </x-card>
                    </div>
                </div>
            </form>
        </div>

        @can('inventory.stock.adjust')
            <div class="col-lg-4">
                <div class="vstack gap-3">
                    <form method="POST" action="{{ route('tenant.products.adjust', $product) }}">
                        @csrf
                        <x-card>
                            <h2 class="h3 mb-3">Adjust stock</h2>
                            <x-input label="Delta (+ in / − out)" name="delta" type="number" required help="Example: 10 or -3" />
                            <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                            <x-button>Apply adjustment</x-button>
                        </x-card>
                    </form>

                    <x-card :padding="false">
                        <div class="card-header fw-medium">Recent movements</div>
                        <ul class="list-group list-group-flush">
                            @forelse ($product->stockMovements as $move)
                                <li class="list-group-item d-flex align-items-center justify-content-between">
                                    <span>
                                        <span class="fw-medium">{{ $move->type }}</span>
                                        <span class="text-secondary">{{ $move->notes }}</span>
                                    </span>
                                    <span class="font-monospace small {{ $move->quantity < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $move->quantity > 0 ? '+' : '' }}{{ $move->quantity }} → {{ $move->balance_after }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item text-secondary">No movements yet.</li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
            </div>
        @endcan
    </div>
@endsection
