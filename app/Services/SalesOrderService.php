<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SalesOrderService
{
    public function confirm(SalesOrder $order, User $user): SalesOrder
    {
        if (! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft orders can be confirmed.');
        }

        $order->load('items.product');

        if ($order->items->isEmpty()) {
            throw new InvalidArgumentException('Order has no items.');
        }

        return DB::connection('tenant')->transaction(function () use ($order, $user) {
            foreach ($order->items as $item) {
                /** @var Product $product */
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                if ($product->stock_qty < $item->quantity) {
                    throw new RuntimeException("Insufficient stock for {$product->name}.");
                }

                $product->stock_qty -= $item->quantity;
                $product->save();

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'balance_after' => $product->stock_qty,
                    'reference_type' => SalesOrder::class,
                    'reference_id' => $order->id,
                    'notes' => "Sale {$order->number}",
                    'user_id' => $user->id,
                ]);
            }

            $order->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return $order->fresh(['customer', 'items.product']);
        });
    }

    public function nextNumber(): string
    {
        $prefix = 'SO-'.now()->format('Ymd').'-';
        $latest = SalesOrder::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
