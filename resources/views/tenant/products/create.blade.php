@extends('layouts.app')

@section('title', 'Create product')
@section('page-title', 'Create product')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">Create product</h1>
        <p class="mt-1 text-sm text-ink-500">Catalog identity on the left, pricing & stock on the right.</p>
    </div>

    <form method="POST" action="{{ route('tenant.products.store') }}" class="space-y-6">
        @csrf
        <div class="grid gap-6 lg:grid-cols-3">
            <x-card class="space-y-4 lg:col-span-2">
                <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus class="text-lg font-medium" />
                <x-input label="SKU" name="sku" value="{{ old('sku') }}" required help="Unique product code" />
                <x-textarea label="Description" name="description" rows="4">{{ old('description') }}</x-textarea>
            </x-card>

            <x-card class="space-y-4">
                <x-input label="Unit" name="unit" value="{{ old('unit', 'pcs') }}" required />
                <x-input label="Sales price (Rp)" name="price" type="number" min="0" value="{{ old('price', 0) }}" required />
                <x-input label="Opening stock" name="stock_qty" type="number" min="0" value="{{ old('stock_qty', 0) }}" required />
                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-line">
                    Active
                </label>
            </x-card>
        </div>

        <div class="flex gap-3">
            <x-button>Create</x-button>
            <x-button href="{{ route('tenant.products.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
