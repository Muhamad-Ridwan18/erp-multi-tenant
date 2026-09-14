@php
    /** @var int|string $index */
    $selected = (string) ($item['product_id'] ?? '');
@endphp
<tr data-line>
    <td>
        <select
            name="items[{{ $index }}][product_id]"
            required
            data-tom-select
            data-product-select
            data-placeholder="Search product…"
            class="form-select"
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
        <div class="form-hint text-warning d-none" data-stock-hint></div>
    </td>
    <td>
        <input type="number" min="1" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required data-qty class="form-control">
    </td>
    <td>
        <input type="number" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" required data-unit-price class="form-control">
    </td>
    <td>
        <input type="number" min="0" max="100" name="items[{{ $index }}][discount_percent]" value="{{ $item['discount_percent'] ?? 0 }}" data-discount class="form-control">
    </td>
    <td>
        <input type="number" min="0" max="100" name="items[{{ $index }}][tax_percent]" value="{{ $item['tax_percent'] ?? 0 }}" data-tax class="form-control">
    </td>
    <td class="text-end fw-medium" data-line-amount>Rp 0</td>
    <td>
        <button type="button" class="btn btn-ghost-danger btn-icon" data-remove-line title="Remove line" aria-label="Remove line">×</button>
    </td>
</tr>
