@extends('layouts.app')

@section('title', 'New purchase order')
@section('page-title', 'New purchase order')

@section('content')
    <div class="max-w-3xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">New purchase order</h1>

        @if ($vendors->isEmpty() || $products->isEmpty())
            <x-alert type="error" class="mb-4">
                Add at least one vendor and one active product before creating a purchase order.
            </x-alert>
        @endif

        <form method="POST" action="{{ route('tenant.purchases.store') }}" class="space-y-6">
            @csrf
            <x-card class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Vendor</label>
                    <select name="vendor_id" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                        <option value="">Select vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                </div>
            </x-card>

            <x-card>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-medium text-ink-900">Line items</h2>
                    <button type="button" class="text-sm text-ink-700 underline" data-add-line>Add line</button>
                </div>
                <div class="space-y-3" data-lines>
                    @php $oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => 0]]); @endphp
                    @foreach ($oldItems as $i => $item)
                        <div class="grid gap-3 sm:grid-cols-[1fr_6rem_8rem_auto]" data-line>
                            <select name="items[{{ $i }}][product_id]" required class="rounded-lg border border-line px-3 py-2 text-sm" data-product-select>
                                <option value="">Product</option>
                                @foreach ($products as $product)
                                    <option
                                        value="{{ $product->id }}"
                                        data-price="{{ $product->price }}"
                                        @selected((string) ($item['product_id'] ?? '') === (string) $product->id)
                                    >
                                        {{ $product->name }} — {{ $product->formattedPrice() }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" min="1" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Qty">
                            <input type="number" min="0" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" required class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Price" data-unit-price>
                            <button type="button" class="text-sm text-red-700" data-remove-line>Remove</button>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <div class="flex gap-3">
                <x-button :disabled="$vendors->isEmpty() || $products->isEmpty()">Create draft</x-button>
                <x-button href="{{ route('tenant.purchases.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>

    <template id="line-template">
        <div class="grid gap-3 sm:grid-cols-[1fr_6rem_8rem_auto]" data-line>
            <select name="items[__INDEX__][product_id]" required class="rounded-lg border border-line px-3 py-2 text-sm" data-product-select>
                <option value="">Product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }} — {{ $product->formattedPrice() }}</option>
                @endforeach
            </select>
            <input type="number" min="1" name="items[__INDEX__][quantity]" value="1" required class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Qty">
            <input type="number" min="0" name="items[__INDEX__][unit_price]" value="0" required class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Price" data-unit-price>
            <button type="button" class="text-sm text-red-700" data-remove-line>Remove</button>
        </div>
    </template>
@endsection
