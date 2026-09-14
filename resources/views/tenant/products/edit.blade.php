@extends('layouts.app')

@section('title', 'Edit product')
@section('page-title', 'Edit product')
@section('page-subtitle', $product->sku)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <h1 class="text-2xl font-semibold text-ink-950">{{ $product->name }}</h1>
        @can('inventory.products.delete')
            <form method="POST" action="{{ route('tenant.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('tenant.products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-card class="space-y-4">
                <x-input label="SKU" name="sku" value="{{ old('sku', $product->sku) }}" required />
                <x-input label="Name" name="name" value="{{ old('name', $product->name) }}" required />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Description</label>
                    <textarea name="description" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('description', $product->description) }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input label="Unit" name="unit" value="{{ old('unit', $product->unit) }}" required />
                    <x-input label="Price (Rp)" name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required />
                </div>
                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))>
                    Active
                </label>
                <p class="text-sm text-ink-500">Current stock: <strong>{{ $product->stock_qty }} {{ $product->unit }}</strong></p>
            </x-card>
            <x-button>Save product</x-button>
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
