@props([
    'products',
    'items' => null,
    'showStock' => false,
])

@php
    $items = $items ?? old('items', [[
        'product_id' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'discount_percent' => 0,
        'tax_percent' => 0,
    ]]);
@endphp

<div class="overflow-hidden rounded-xl border border-line bg-panel shadow-sm" data-document-lines>
    <div class="flex items-center justify-between border-b border-line px-4 py-3">
        <h2 class="font-medium text-ink-900">Order lines</h2>
        <button type="button" class="text-sm font-medium text-ink-700 hover:text-ink-950" data-add-line>+ Add a line</button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[52rem] text-sm">
            <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Product</th>
                    <th class="w-24 px-2 py-2.5 font-medium">Qty</th>
                    <th class="w-28 px-2 py-2.5 font-medium">Unit price</th>
                    <th class="w-20 px-2 py-2.5 font-medium">Disc %</th>
                    <th class="w-20 px-2 py-2.5 font-medium">Tax %</th>
                    <th class="w-32 px-2 py-2.5 font-medium text-right">Amount</th>
                    <th class="w-10 px-2 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line" data-lines>
                @foreach ($items as $i => $item)
                    @include('components.partials.document-line-row', [
                        'index' => $i,
                        'item' => $item,
                        'products' => $products,
                        'showStock' => $showStock,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="space-y-1.5 border-t border-line bg-ink-50/60 px-4 py-3 text-sm">
        <div class="flex justify-end gap-8">
            <span class="text-ink-500">Untaxed</span>
            <span class="min-w-[8rem] text-right tabular-nums text-ink-800" data-doc-subtotal>Rp 0</span>
        </div>
        <div class="flex justify-end gap-8">
            <span class="text-ink-500">Discount</span>
            <span class="min-w-[8rem] text-right tabular-nums text-ink-800" data-doc-discount>Rp 0</span>
        </div>
        <div class="flex justify-end gap-8">
            <span class="text-ink-500">Tax</span>
            <span class="min-w-[8rem] text-right tabular-nums text-ink-800" data-doc-tax>Rp 0</span>
        </div>
        <div class="flex justify-end gap-8 border-t border-line pt-2">
            <span class="font-medium text-ink-900">Total</span>
            <span class="min-w-[8rem] text-right text-base font-semibold tabular-nums text-ink-950" data-doc-total>Rp 0</span>
        </div>
    </div>
</div>

<template id="line-template">
    @include('components.partials.document-line-row', [
        'index' => '__INDEX__',
        'item' => ['product_id' => '', 'quantity' => 1, 'unit_price' => 0, 'discount_percent' => 0, 'tax_percent' => 0],
        'products' => $products,
        'showStock' => $showStock,
    ])
</template>
