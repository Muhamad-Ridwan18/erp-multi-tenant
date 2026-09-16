<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(protected InventoryOperationService $inventory) {}

    public function confirm(PurchaseOrder $order): PurchaseOrder
    {
        if (! $order->isDraft() && $order->status !== 'sent') {
            throw new InvalidArgumentException('Only draft/sent purchase orders can be confirmed.');
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

    public function send(PurchaseOrder $order): PurchaseOrder
    {
        if (! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft RFQs can be sent.');
        }

        $order->update(['status' => 'sent']);

        return $order->fresh();
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (! in_array($order->status, ['draft', 'sent', 'confirmed'], true)) {
            throw new InvalidArgumentException('Purchase order cannot be canceled.');
        }

        $order->update(['status' => 'canceled']);

        return $order->fresh();
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int}>|null  $lines  null = receive all remaining
     */
    public function receive(PurchaseOrder $order, User $user, ?array $lines = null): PurchaseOrder
    {
        if (! $order->isConfirmed() && ! $order->isReceived()) {
            throw new InvalidArgumentException('Only confirmed purchase orders can be received.');
        }

        $order->load('items');

        if ($lines === null) {
            $lines = $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity - $item->qty_received,
                'uom_id' => $item->uom_id,
            ])->all();
        }

        $this->inventory->createReceiptFromPurchase($order, $lines, $user);

        return $order->fresh(['vendor', 'items.product']);
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
