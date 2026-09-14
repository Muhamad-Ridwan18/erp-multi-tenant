@extends('layouts.app')

@section('title', 'Create product')
@section('page-title', 'Create product')

@section('content')
    <div class="max-w-xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">Create product</h1>
        <form method="POST" action="{{ route('tenant.products.store') }}" class="space-y-6">
            @csrf
            <x-card class="space-y-4">
                <x-input label="SKU" name="sku" value="{{ old('sku') }}" required autofocus />
                <x-input label="Name" name="name" value="{{ old('name') }}" required />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Description</label>
                    <textarea name="description" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('description') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-input label="Unit" name="unit" value="{{ old('unit', 'pcs') }}" required />
                    <x-input label="Price (Rp)" name="price" type="number" min="0" value="{{ old('price', 0) }}" required />
                    <x-input label="Opening stock" name="stock_qty" type="number" min="0" value="{{ old('stock_qty', 0) }}" required />
                </div>
                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                    Active
                </label>
            </x-card>
            <div class="flex gap-3">
                <x-button>Create</x-button>
                <x-button href="{{ route('tenant.products.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>
@endsection
