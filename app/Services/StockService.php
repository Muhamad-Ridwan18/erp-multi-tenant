<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockQuant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function __construct(protected InventoryOperationService $operations) {}

    public function adjust(Product $product, int $delta, User $user, ?string $notes = null, ?Location $location = null): Product
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        $location ??= $this->operations->stockLocation();

        return DB::connection('tenant')->transaction(function () use ($product, $delta, $user, $notes, $location) {
            $quant = StockQuant::query()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0]
            );

            /** @var StockQuant $lockedQuant */
            $lockedQuant = StockQuant::query()->lockForUpdate()->findOrFail($quant->id);
            $newQty = $lockedQuant->quantity + $delta;
            if ($newQty < 0) {
                throw new InvalidArgumentException('Stock cannot go below zero.');
            }
            $lockedQuant->quantity = $newQty;
            $lockedQuant->save();

            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $internalIds = Location::query()->where('type', 'internal')->pluck('id');
            $locked->stock_qty = (int) StockQuant::query()
                ->where('product_id', $locked->id)
                ->whereIn('location_id', $internalIds)
                ->sum('quantity');
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
