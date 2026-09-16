<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\StockOperation;
use App\Models\StockQuant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryOperationService
{
    public function nextNumber(string $type): string
    {
        $map = [
            'receipt' => 'IN',
            'delivery' => 'OUT',
            'internal' => 'INT',
            'scrap' => 'SCR',
            'manufacture' => 'MFG',
            'unbuild' => 'UNB',
        ];
        $prefix = ($map[$type] ?? 'STK').'-'.now()->format('Ymd').'-';
        $latest = StockOperation::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');
        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function stockLocation(): Location
    {
        return Location::query()->where('code', 'STOCK')->where('type', 'internal')->firstOrFail();
    }

    public function vendorLocation(): Location
    {
        return Location::query()->where('code', 'VENDORS')->firstOrFail();
    }

    public function customerLocation(): Location
    {
        return Location::query()->where('code', 'CUSTOMERS')->firstOrFail();
    }

    public function scrapLocation(): Location
    {
        return Location::query()->firstOrCreate(
            ['code' => 'SCRAP', 'warehouse_id' => null],
            ['name' => 'Scrap', 'type' => 'inventory', 'is_active' => true]
        );
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, uom_id?:int|null}>  $lines
     */
    public function createSimpleOperation(
        string $type,
        Location $source,
        Location $destination,
        array $lines,
        User $user,
        ?string $origin = null,
        ?int $manufacturingOrderId = null,
        ?string $notes = null,
    ): StockOperation {
        return DB::connection('tenant')->transaction(function () use ($type, $source, $destination, $lines, $user, $origin, $manufacturingOrderId, $notes) {
            $operation = StockOperation::query()->create([
                'number' => $this->nextNumber($type),
                'type' => $type,
                'status' => 'draft',
                'source_location_id' => $source->id,
                'destination_location_id' => $destination->id,
                'manufacturing_order_id' => $manufacturingOrderId,
                'origin' => $origin,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $qty = (int) ($line['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $operation->moves()->create([
                    'product_id' => (int) $line['product_id'],
                    'uom_id' => $line['uom_id'] ?? null,
                    'lot_id' => $line['lot_id'] ?? null,
                    'demand_qty' => $qty,
                    'done_qty' => $qty,
                ]);
            }

            if ($operation->moves()->doesntExist()) {
                throw new InvalidArgumentException('No quantities for stock operation.');
            }

            return $this->validate($operation->fresh('moves'), $user);
        });
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, uom_id?:int|null}>  $lines
     */
    public function createScrap(array $lines, User $user, ?Location $source = null, ?string $notes = null): StockOperation
    {
        return $this->createSimpleOperation(
            type: 'scrap',
            source: $source ?? $this->stockLocation(),
            destination: $this->scrapLocation(),
            lines: $lines,
            user: $user,
            notes: $notes ?? 'Scrap',
        );
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, uom_id?:int|null}>  $lines
     */
    public function createReceiptFromPurchase(PurchaseOrder $order, array $lines, User $user): StockOperation
    {
        if (! $order->isConfirmed() && ! $order->isReceived()) {
            throw new InvalidArgumentException('Only confirmed purchase orders can receive goods.');
        }

        $order->load('items');
        $dest = $order->destination_location_id
            ? Location::query()->findOrFail($order->destination_location_id)
            : $this->stockLocation();

        return DB::connection('tenant')->transaction(function () use ($order, $lines, $user, $dest) {
            $operation = StockOperation::query()->create([
                'number' => $this->nextNumber('receipt'),
                'type' => 'receipt',
                'status' => 'draft',
                'source_location_id' => $this->vendorLocation()->id,
                'destination_location_id' => $dest->id,
                'partner_vendor_id' => $order->vendor_id,
                'purchase_order_id' => $order->id,
                'origin' => $order->number,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $qty = (int) ($line['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $item = $order->items->firstWhere('product_id', (int) $line['product_id']);
                if (! $item) {
                    throw new InvalidArgumentException('Product not on purchase order.');
                }
                $remaining = $item->quantity - $item->qty_received;
                if ($qty > $remaining) {
                    throw new InvalidArgumentException("Cannot receive more than remaining qty for product #{$item->product_id}.");
                }

                $operation->moves()->create([
                    'product_id' => $item->product_id,
                    'uom_id' => $line['uom_id'] ?? $item->uom_id,
                    'demand_qty' => $qty,
                    'done_qty' => $qty,
                ]);
            }

            if ($operation->moves()->doesntExist()) {
                throw new InvalidArgumentException('No quantities to receive.');
            }

            return $this->validate($operation->fresh('moves'), $user);
        });
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, uom_id?:int|null}>|null  $lines
     */
    public function createDeliveryFromSalesOrder(SalesOrder $order, User $user, ?array $lines = null): StockOperation
    {
        if (! $order->isConfirmed()) {
            throw new InvalidArgumentException('Only confirmed sales orders can be delivered.');
        }

        $order->load('items');
        $source = $this->stockLocation();

        return DB::connection('tenant')->transaction(function () use ($order, $user, $lines, $source) {
            $operation = StockOperation::query()->create([
                'number' => $this->nextNumber('delivery'),
                'type' => 'delivery',
                'status' => 'draft',
                'source_location_id' => $source->id,
                'destination_location_id' => $this->customerLocation()->id,
                'partner_customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'origin' => $order->number,
                'created_by' => $user->id,
            ]);

            $payload = $lines;
            if ($payload === null) {
                $payload = $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity - $item->qty_delivered,
                    'uom_id' => $item->uom_id,
                ])->all();
            }

            foreach ($payload as $line) {
                $qty = (int) ($line['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $item = $order->items->firstWhere('product_id', (int) $line['product_id']);
                if (! $item) {
                    throw new InvalidArgumentException('Product not on sales order.');
                }
                $remaining = $item->quantity - $item->qty_delivered;
                if ($qty > $remaining) {
                    throw new InvalidArgumentException('Cannot deliver more than remaining qty.');
                }

                $operation->moves()->create([
                    'product_id' => $item->product_id,
                    'uom_id' => $line['uom_id'] ?? $item->uom_id,
                    'demand_qty' => $qty,
                    'done_qty' => $qty,
                ]);
            }

            if ($operation->moves()->doesntExist()) {
                throw new InvalidArgumentException('No quantities to deliver.');
            }

            return $this->validate($operation->fresh('moves'), $user);
        });
    }

    public function validate(StockOperation $operation, User $user): StockOperation
    {
        if ($operation->status !== 'draft') {
            throw new InvalidArgumentException('Only draft operations can be validated.');
        }

        $operation->load('moves');

        return DB::connection('tenant')->transaction(function () use ($operation, $user) {
            foreach ($operation->moves as $move) {
                $qty = (int) $move->done_qty;
                if ($qty <= 0) {
                    continue;
                }

                if ($operation->source_location_id) {
                    $this->applyQuantDelta($move->product_id, $operation->source_location_id, -$qty, $user, $operation, $move->lot_id);
                }
                if ($operation->destination_location_id) {
                    $this->applyQuantDelta($move->product_id, $operation->destination_location_id, $qty, $user, $operation, $move->lot_id);
                }

                $this->syncProductStockCache($move->product_id);
            }

            $operation->update([
                'status' => 'done',
                'done_at' => now(),
            ]);

            if ($operation->purchase_order_id) {
                $this->syncPurchaseReceipt($operation);
            }
            if ($operation->sales_order_id) {
                $this->syncSalesDelivery($operation);
            }

            return $operation->fresh(['moves.product', 'purchaseOrder', 'salesOrder']);
        });
    }

    protected function applyQuantDelta(int $productId, int $locationId, int $delta, User $user, StockOperation $operation, ?int $lotId = null): void
    {
        $quant = StockQuant::query()->firstOrCreate(
            ['product_id' => $productId, 'location_id' => $locationId, 'lot_id' => $lotId],
            ['quantity' => 0]
        );

        /** @var StockQuant $locked */
        $locked = StockQuant::query()->lockForUpdate()->findOrFail($quant->id);
        $newQty = $locked->quantity + $delta;
        if ($newQty < 0 && Location::query()->whereKey($locationId)->value('type') === 'internal') {
            $product = Product::query()->find($productId);
            throw new RuntimeException('Insufficient stock for '.($product?->name ?? "#{$productId}").'.');
        }
        $locked->quantity = $newQty;
        $locked->save();

        $product = Product::query()->lockForUpdate()->findOrFail($productId);
        StockMovement::query()->create([
            'product_id' => $productId,
            'type' => $operation->type,
            'quantity' => $delta,
            'balance_after' => max(0, $product->stock_qty + $delta),
            'reference_type' => StockOperation::class,
            'reference_id' => $operation->id,
            'notes' => "{$operation->type} {$operation->number}".($lotId ? " lot#{$lotId}" : ''),
            'user_id' => $user->id,
        ]);
    }

    protected function syncProductStockCache(int $productId): void
    {
        $internal = Location::query()->where('type', 'internal')->pluck('id');
        $sum = (int) StockQuant::query()
            ->where('product_id', $productId)
            ->whereIn('location_id', $internal)
            ->sum('quantity');

        Product::query()->whereKey($productId)->update(['stock_qty' => $sum]);
    }

    protected function syncPurchaseReceipt(StockOperation $operation): void
    {
        $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($operation->purchase_order_id);
        $order->load('items');

        foreach ($operation->moves as $move) {
            $item = $order->items->firstWhere('product_id', $move->product_id);
            if ($item) {
                $item->qty_received += $move->done_qty;
                $item->save();
            }
        }

        $order->load('items');
        $order->updateReceiptStatus();
        if ($order->receipt_status === 'full') {
            $order->update(['status' => 'received', 'received_at' => now()]);
        }
    }

    protected function syncSalesDelivery(StockOperation $operation): void
    {
        $order = SalesOrder::query()->lockForUpdate()->findOrFail($operation->sales_order_id);
        $order->load('items');

        foreach ($operation->moves as $move) {
            $item = $order->items->firstWhere('product_id', $move->product_id);
            if ($item) {
                $item->qty_delivered += $move->done_qty;
                $item->save();
            }
        }

        $order->load('items');
        $total = $order->items->sum('quantity');
        $delivered = $order->items->sum('qty_delivered');
        $status = $delivered <= 0 ? 'no' : ($delivered >= $total ? 'full' : 'partial');
        $order->update(['delivery_status' => $status]);
    }
}
