<?php

namespace App\Support;

use App\Models\Product;

class DocumentLineDescriber
{
    /**
     * Fill in a label for every line so manual invoice/bill items are readable
     * without loading the product relation.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function describe(array $items): array
    {
        $productIds = array_filter(array_map(fn (array $item) => $item['product_id'] ?? null, $items));

        $names = $productIds === []
            ? []
            : Product::query()->whereKey($productIds)->pluck('name', 'id')->all();

        return array_map(function (array $item) use ($names) {
            if (empty($item['description'])) {
                $item['description'] = $names[$item['product_id'] ?? null] ?? 'Item';
            }

            return $item;
        }, $items);
    }
}
