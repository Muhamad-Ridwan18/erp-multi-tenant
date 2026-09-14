@extends('layouts.app')

@section('title', 'Edit product')
@section('page-title', 'Edit product')
@section('page-subtitle', $product->sku)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-ink-500">Stock on hand: <strong>{{ $product->stock_qty }} {{ $product->unit }}</strong></p>
        </div>
        @can('inventory.products.delete')
            <form method="POST" action="{{ route('tenant.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('tenant.products.update', $product) }}" class="space-y-6 lg:col-span-2">
            @csrf
            @method('PUT')
            <div class="grid gap-6 sm:grid-cols-3">
                <x-card class="space-y-4 sm:col-span-2">
                    <x-input label="Name" name="name" value="{{ old('name', $product->name) }}" required class="text-lg font-medium" />
                    <x-input label="SKU" name="sku" value="{{ old('sku', $product->sku) }}" required />
                    <x-textarea label="Description" name="description" rows="4">{{ old('description', $product->description) }}</x-textarea>
                </x-card>
                <x-card class="space-y-4">
                    <x-input label="Unit" name="unit" value="{{ old('unit', $product->unit) }}" required />
                    <x-input label="Sales price (Rp)" name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required />
                    <label class="flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-line">
                        Active
                    </label>
                    <x-button>Save product</x-button>
                </x-card>
            </div>
        </form>

        @can('inventory.stock.adjust')
            <div class="space-y-6">
                <form method="POST" action="{{ route('tenant.products.adjust', $product) }}" class="space-y-4">
                    @csrf
                    <x-card class="space-y-4">
                        <h2 class="font-medium text-ink-900">Adjust stock</h2>
                        <x-input label="Delta (+ in / − out)" name="delta" type="number" required help="Example: 10 or -3" />
                        <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                        <x-button>Apply adjustment</x-button>
                    </x-card>
                </form>

                <x-card :padding="false">
                    <div class="border-b border-line px-6 py-4 font-medium text-ink-900">Recent movements</div>
                    <ul class="divide-y divide-line text-sm">
                        @forelse ($product->stockMovements as $move)
                            <li class="flex items-center justify-between px-6 py-3">
                                <span>
                                    <span class="font-medium">{{ $move->type }}</span>
                                    <span class="text-ink-500">{{ $move->notes }}</span>
                                </span>
                                <span class="font-mono text-xs {{ $move->quantity < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                    {{ $move->quantity > 0 ? '+' : '' }}{{ $move->quantity }} → {{ $move->balance_after }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-ink-500">No movements yet.</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>
        @endcan
    </div>
@endsection
