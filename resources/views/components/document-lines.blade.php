@props([
    'products',
    'items' => null,
    'showStock' => false,
])

@php
    $items = $items ?? old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => 0]]);
@endphp

<div class="overflow-hidden rounded-xl border border-line bg-panel shadow-sm" data-document-lines>
    <div class="flex items-center justify-between border-b border-line px-4 py-3">
        <h2 class="font-medium text-ink-900">Order lines</h2>
        <button type="button" class="text-sm font-medium text-ink-700 hover:text-ink-950" data-add-line>+ Add a line</button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[40rem] text-sm">
            <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Product</th>
                    <th class="w-28 px-3 py-2.5 font-medium">Qty</th>
                    <th class="w-36 px-3 py-2.5 font-medium">Unit price</th>
                    <th class="w-36 px-3 py-2.5 font-medium text-right">Amount</th>
                    <th class="w-12 px-2 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line" data-lines>
                @foreach ($items as $i => $item)
                    <tr data-line>
                        <td class="px-3 py-2 align-middle">
                            <select
                                name="items[{{ $i }}][product_id]"
                                required
                                data-tom-select
                                data-product-select
                                data-placeholder="Search product…"
                                class="w-full rounded-lg border border-line px-2 py-1.5 text-sm"
                            >
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option
                                        value="{{ $product->id }}"
                                        data-price="{{ $product->price }}"
                                        data-stock="{{ $product->stock_qty }}"
                                        data-unit="{{ $product->unit }}"
                                        @selected((string) ($item['product_id'] ?? '') === (string) $product->id)
                                    >
                                        {{ $product->sku }} — {{ $product->name }}@if ($showStock) (stock {{ $product->stock_qty }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 hidden text-xs text-amber-700" data-stock-hint></p>
                        </td>
                        <td class="px-3 py-2 align-middle">
                            <input
                                type="number"
                                min="1"
                                name="items[{{ $i }}][quantity]"
                                value="{{ $item['quantity'] ?? 1 }}"
                                required
                                data-qty
                                class="w-full rounded-lg border border-line px-2 py-1.5 text-sm"
                            >
                        </td>
                        <td class="px-3 py-2 align-middle">
                            <input
                                type="number"
                                min="0"
                                name="items[{{ $i }}][unit_price]"
                                value="{{ $item['unit_price'] ?? 0 }}"
                                required
                                data-unit-price
                                class="w-full rounded-lg border border-line px-2 py-1.5 text-sm"
                            >
                        </td>
                        <td class="px-3 py-2 align-middle text-right font-medium tabular-nums text-ink-800" data-line-amount>
                            Rp 0
                        </td>
                        <td class="px-2 py-2 align-middle text-center">
                            <button type="button" class="rounded p-1 text-ink-400 hover:bg-red-50 hover:text-red-700" data-remove-line title="Remove line" aria-label="Remove line">×</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-end gap-8 border-t border-line bg-ink-50/60 px-4 py-3">
        <div class="text-sm text-ink-500">Subtotal</div>
        <div class="min-w-[8rem] text-right text-base font-semibold tabular-nums text-ink-950" data-doc-subtotal>Rp 0</div>
    </div>
</div>

<template id="line-template">
    <tr data-line>
        <td class="px-3 py-2 align-middle">
            <select
                name="items[__INDEX__][product_id]"
                required
                data-tom-select
                data-product-select
                data-placeholder="Search product…"
                class="w-full rounded-lg border border-line px-2 py-1.5 text-sm"
            >
                <option value="">Select product</option>
                @foreach ($products as $product)
                    <option
                        value="{{ $product->id }}"
                        data-price="{{ $product->price }}"
                        data-stock="{{ $product->stock_qty }}"
                        data-unit="{{ $product->unit }}"
                    >
                        {{ $product->sku }} — {{ $product->name }}@if ($showStock) (stock {{ $product->stock_qty }})@endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 hidden text-xs text-amber-700" data-stock-hint></p>
        </td>
        <td class="px-3 py-2 align-middle">
            <input type="number" min="1" name="items[__INDEX__][quantity]" value="1" required data-qty class="w-full rounded-lg border border-line px-2 py-1.5 text-sm">
        </td>
        <td class="px-3 py-2 align-middle">
            <input type="number" min="0" name="items[__INDEX__][unit_price]" value="0" required data-unit-price class="w-full rounded-lg border border-line px-2 py-1.5 text-sm">
        </td>
        <td class="px-3 py-2 align-middle text-right font-medium tabular-nums text-ink-800" data-line-amount>Rp 0</td>
        <td class="px-2 py-2 align-middle text-center">
            <button type="button" class="rounded p-1 text-ink-400 hover:bg-red-50 hover:text-red-700" data-remove-line title="Remove line" aria-label="Remove line">×</button>
        </td>
    </tr>
</template>
