@php
    /** @var int|string $index */
    $selected = (string) ($item['product_id'] ?? '');
@endphp
<tr data-line>
    <td class="px-3 py-2 align-middle">
        <select
            name="items[{{ $index }}][product_id]"
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
                    @selected($selected === (string) $product->id)
                >
                    {{ $product->sku }} — {{ $product->name }}@if ($showStock) (stock {{ $product->stock_qty }})@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 hidden text-xs text-amber-700" data-stock-hint></p>
    </td>
    <td class="px-2 py-2 align-middle">
        <input type="number" min="1" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required data-qty class="w-full rounded-lg border border-line px-2 py-1.5 text-sm">
    </td>
    <td class="px-2 py-2 align-middle">
        <input type="number" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" required data-unit-price class="w-full rounded-lg border border-line px-2 py-1.5 text-sm">
    </td>
    <td class="px-2 py-2 align-middle">
        <input type="number" min="0" max="100" name="items[{{ $index }}][discount_percent]" value="{{ $item['discount_percent'] ?? 0 }}" data-discount class="w-full rounded-lg border border-line px-2 py-1.5 text-sm">
    </td>
    <td class="px-2 py-2 align-middle">
        <input type="number" min="0" max="100" name="items[{{ $index }}][tax_percent]" value="{{ $item['tax_percent'] ?? 0 }}" data-tax class="w-full rounded-lg border border-line px-2 py-1.5 text-sm">
    </td>
    <td class="px-2 py-2 align-middle text-right font-medium tabular-nums text-ink-800" data-line-amount>Rp 0</td>
    <td class="px-2 py-2 align-middle text-center">
        <button type="button" class="rounded p-1 text-ink-400 hover:bg-red-50 hover:text-red-700" data-remove-line title="Remove line" aria-label="Remove line">×</button>
    </td>
</tr>
