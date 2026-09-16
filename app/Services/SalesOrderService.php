<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderService
{
    public function __construct(protected InventoryOperationService $inventory) {}

    public function confirm(SalesOrder $order, User $user): SalesOrder
    {
        if (! $order->isDraft() && ! $order->isQuotation()) {
            throw new InvalidArgumentException('Only draft quotations/orders can be confirmed.');
        }

        $order->load('items.product');

        if ($order->items->isEmpty()) {
            throw new InvalidArgumentException('Order has no items.');
        }

        return DB::connection('tenant')->transaction(function () use ($order) {
            // Stock is deducted on delivery validation, not on confirm.
            $order->update([
                'status' => 'confirmed',
                'kind' => 'order',
                'confirmed_at' => now(),
                'invoice_status' => 'to_invoice',
            ]);

            return $order->fresh(['customer', 'items.product']);
        });
    }

    public function sendQuotation(SalesOrder $order): SalesOrder
    {
        if ($order->kind !== 'quotation' || $order->status !== 'draft') {
            throw new InvalidArgumentException('Only draft quotations can be sent.');
        }

        $order->update(['status' => 'sent']);

        return $order->fresh();
    }

    public function cancel(SalesOrder $order): SalesOrder
    {
        if (! in_array($order->status, ['draft', 'sent', 'confirmed'], true)) {
            throw new InvalidArgumentException('Order cannot be canceled.');
        }

        $order->update(['status' => 'canceled']);

        return $order->fresh();
    }

    public function deliver(SalesOrder $order, User $user, ?array $lines = null): SalesOrder
    {
        $this->inventory->createDeliveryFromSalesOrder($order, $user, $lines);

        return $order->fresh(['customer', 'items.product', 'deliveries']);
    }

    public function nextNumber(string $kind = 'order'): string
    {
        $tag = $kind === 'quotation' ? 'QT' : 'SO';
        $prefix = $tag.'-'.now()->format('Ymd').'-';
        $latest = SalesOrder::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
