<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function confirm(PurchaseOrder $order): PurchaseOrder
    {
        if (! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft purchase orders can be confirmed.');
        }

        $order->load('items');

        if ($order->items->isEmpty()) {
            throw new InvalidArgumentException('Purchase order has no items.');
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $order->fresh(['vendor', 'items.product']);
    }

    public function receive(PurchaseOrder $order, User $user): PurchaseOrder
    {
        if (! $order->isConfirmed()) {
            throw new InvalidArgumentException('Only confirmed purchase orders can be received.');
        }

        $order->load('items.product');

        if ($order->items->isEmpty()) {
            throw new InvalidArgumentException('Purchase order has no items.');
        }

        return DB::connection('tenant')->transaction(function () use ($order, $user) {
            foreach ($order->items as $item) {
                /** @var Product $product */
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                $product->stock_qty += $item->quantity;
                $product->save();

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => 'purchase',
                    'quantity' => $item->quantity,
                    'balance_after' => $product->stock_qty,
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $order->id,
                    'notes' => "Purchase {$order->number}",
                    'user_id' => $user->id,
                ]);
            }

            $order->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            return $order->fresh(['vendor', 'items.product']);
        });
    }

    public function nextNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';
        $latest = PurchaseOrder::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
