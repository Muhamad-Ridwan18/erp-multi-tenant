<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function adjust(Product $product, int $delta, User $user, ?string $notes = null): Product
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return DB::connection('tenant')->transaction(function () use ($product, $delta, $user, $notes) {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $newQty = $locked->stock_qty + $delta;

            if ($newQty < 0) {
                throw new InvalidArgumentException('Stock cannot go below zero.');
            }

            $locked->stock_qty = $newQty;
            $locked->save();

            StockMovement::query()->create([
                'product_id' => $locked->id,
                'type' => 'adjust',
                'quantity' => $delta,
                'balance_after' => $locked->stock_qty,
                'notes' => $notes,
                'user_id' => $user->id,
            ]);

            return $locked;
        });
    }
}
