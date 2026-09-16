@extends('layouts.app')

@section('title', 'Create MO')
@section('page-title', 'Create manufacturing order')
@section('page-subtitle', 'Manufacturing')

@section('content')
    <form method="POST" action="{{ route('tenant.manufacturing-orders.store') }}" class="vstack gap-3">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <x-card>
                    <x-select label="Finished product" name="product_id" placeholder="Search product…" required>
                        <option value="">Select product with BOM</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </x-select>
                    <x-select label="Bill of materials" name="bill_of_material_id" placeholder="Auto latest…">
                        <option value="">Latest active BOM</option>
                        @foreach ($boms as $bom)
                            <option value="{{ $bom->id }}" @selected((string) old('bill_of_material_id') === (string) $bom->id)>
                                {{ $bom->code ?: '#'.$bom->id }} — {{ $bom->product?->name }} (×{{ $bom->quantity }})
                            </option>
                        @endforeach
                    </x-select>
                    <div class="row">
                        <div class="col-md-4">
                            <x-input label="Quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required />
                        </div>
                        <div class="col-md-4">
                            <x-select label="Work center" name="work_center_id">
                                <option value="">From BOM</option>
                                @foreach ($workCenters as $center)
                                    <option value="{{ $center->id }}" @selected((string) old('work_center_id') === (string) $center->id)>{{ $center->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-4">
                            <x-input label="Scheduled at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}" />
                        </div>
                    </div>
                    <x-input label="Origin" name="origin" value="{{ old('origin') }}" />
                    <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
                </x-card>
            </div>
            <div class="col-lg-4">
                <x-card>
                    <x-button class="w-100">Create draft</x-button>
                    <a href="{{ route('tenant.manufacturing-orders.index') }}" class="btn btn-ghost-secondary w-100 mt-2">Cancel</a>
                </x-card>
            </div>
        </div>
    </form>
@endsection
