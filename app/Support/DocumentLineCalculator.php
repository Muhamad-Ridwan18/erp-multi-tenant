<?php

namespace App\Support;

class DocumentLineCalculator
{
    /**
     * @return array{base: int, discount: int, after_discount: int, tax: int, line_total: int}
     */
    public static function calculate(int $quantity, int $unitPrice, int $discountPercent = 0, int $taxPercent = 0): array
    {
        $base = $quantity * $unitPrice;
        $discount = (int) round($base * max(0, min(100, $discountPercent)) / 100);
        $afterDiscount = $base - $discount;
        $tax = (int) round($afterDiscount * max(0, min(100, $taxPercent)) / 100);

        return [
            'base' => $base,
            'discount' => $discount,
            'after_discount' => $afterDiscount,
            'tax' => $tax,
            'line_total' => $afterDiscount + $tax,
        ];
    }

    /**
     * @param  array<int, array{quantity: int, unit_price: int, discount_percent?: int, tax_percent?: int}>  $items
     * @return array{subtotal: int, discount_total: int, tax_total: int, grand_total: int, lines: list<array<string, int|string>>}
     */
    public static function summarize(array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;
        $lines = [];

        foreach ($items as $row) {
            $calc = self::calculate(
                (int) $row['quantity'],
                (int) $row['unit_price'],
                (int) ($row['discount_percent'] ?? 0),
                (int) ($row['tax_percent'] ?? 0),
            );

            $subtotal += $calc['after_discount'];
            $discountTotal += $calc['discount'];
            $taxTotal += $calc['tax'];
            $grandTotal += $calc['line_total'];

            $lines[] = [
                ...$row,
                'discount_percent' => (int) ($row['discount_percent'] ?? 0),
                'tax_percent' => (int) ($row['tax_percent'] ?? 0),
                'line_total' => $calc['line_total'],
            ];
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'lines' => $lines,
        ];
    }
}
