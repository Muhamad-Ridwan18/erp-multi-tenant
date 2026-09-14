@extends('layouts.app')

@section('title', 'New sales order')
@section('page-title', 'New sales order')

@section('content')
    <div class="max-w-3xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">New sales order</h1>

        @if ($customers->isEmpty() || $products->isEmpty())
            <x-alert type="error" class="mb-4">
                Add at least one customer and one active product before creating an order.
            </x-alert>
        @endif

        <form method="POST" action="{{ route('tenant.orders.store') }}" class="space-y-6" id="order-form">
            @csrf
            <x-card class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Customer</label>
                    <select name="customer_id" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                        <option value="">Select customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
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
                    @php $oldItems = old('items', [['product_id' => '', 'quantity' => 1]]); @endphp
                    @foreach ($oldItems as $i => $item)
                        <div class="grid gap-3 sm:grid-cols-[1fr_8rem_auto]" data-line>
                            <select name="items[{{ $i }}][product_id]" required class="rounded-lg border border-line px-3 py-2 text-sm">
                                <option value="">Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
                                        {{ $product->name }} — {{ $product->formattedPrice() }} (stock {{ $product->stock_qty }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" min="1" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Qty">
                            <button type="button" class="text-sm text-red-700" data-remove-line>Remove</button>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <div class="flex gap-3">
                <x-button :disabled="$customers->isEmpty() || $products->isEmpty()">Create draft</x-button>
                <x-button href="{{ route('tenant.orders.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>

    <template id="line-template">
        <div class="grid gap-3 sm:grid-cols-[1fr_8rem_auto]" data-line>
            <select name="items[__INDEX__][product_id]" required class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} — {{ $product->formattedPrice() }} (stock {{ $product->stock_qty }})</option>
                @endforeach
            </select>
            <input type="number" min="1" name="items[__INDEX__][quantity]" value="1" required class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Qty">
            <button type="button" class="text-sm text-red-700" data-remove-line>Remove</button>
        </div>
    </template>
@endsection
