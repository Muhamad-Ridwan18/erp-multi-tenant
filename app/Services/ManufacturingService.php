<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\Location;
use App\Models\Lot;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\UnbuildOrder;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManufacturingService
{
    public function __construct(protected InventoryOperationService $operations) {}

    public function nextNumber(string $prefix = 'MO'): string
    {
        $full = $prefix.'-'.now()->format('Ymd').'-';
        $model = $prefix === 'UB' ? UnbuildOrder::query() : ManufacturingOrder::query();
        $latest = $model
            ->where('number', 'like', $full.'%')
            ->orderByDesc('number')
            ->value('number');
        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $full.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array{product_id:int, bill_of_material_id?:int|null, work_center_id?:int|null, quantity:int, origin?:string|null, notes?:string|null, scheduled_at?:string|null}  $data
     */
    public function create(array $data, User $user): ManufacturingOrder
    {
        $bom = null;
        if (! empty($data['bill_of_material_id'])) {
            $bom = BillOfMaterial::query()->with(['lines', 'operations'])->findOrFail($data['bill_of_material_id']);
            if ((int) $bom->product_id !== (int) $data['product_id']) {
                throw new InvalidArgumentException('BOM product does not match manufacturing order product.');
            }
        } else {
            $bom = BillOfMaterial::query()
                ->with(['lines', 'operations'])
                ->where('product_id', $data['product_id'])
                ->where('is_active', true)
                ->latest('id')
                ->first();
        }

        if (! $bom || $bom->lines->isEmpty()) {
            throw new InvalidArgumentException('An active bill of materials with components is required.');
        }

        $qty = max(1, (int) $data['quantity']);
        $bomQty = max(1, (int) $bom->quantity);
        $stock = $this->operations->stockLocation();

        return DB::connection('tenant')->transaction(function () use ($data, $user, $bom, $qty, $bomQty, $stock) {
            $order = ManufacturingOrder::query()->create([
                'number' => $this->nextNumber('MO'),
                'status' => 'draft',
                'product_id' => $data['product_id'],
                'bill_of_material_id' => $bom->id,
                'work_center_id' => $data['work_center_id'] ?? $bom->work_center_id,
                'uom_id' => $bom->uom_id,
                'quantity' => $qty,
                'qty_produced' => 0,
                'source_location_id' => $stock->id,
                'destination_location_id' => $stock->id,
                'origin' => $data['origin'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($bom->lines as $line) {
                $needed = (int) ceil(($line->quantity * $qty) / $bomQty);
                $order->components()->create([
                    'product_id' => $line->product_id,
                    'uom_id' => $line->uom_id,
                    'quantity' => $needed,
                    'qty_consumed' => 0,
                ]);
            }

            foreach ($bom->operations as $operation) {
                $order->workOrders()->create([
                    'bom_operation_id' => $operation->id,
                    'work_center_id' => $operation->work_center_id ?? $order->work_center_id,
                    'name' => $operation->name,
                    'status' => 'pending',
                    'duration_minutes' => $operation->duration_minutes,
                    'sort' => $operation->sort,
                ]);
            }

            return $order->fresh(['product', 'components.product', 'billOfMaterial', 'workCenter', 'workOrders']);
        });
    }

    public function confirm(ManufacturingOrder $order): ManufacturingOrder
    {
        if (! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft manufacturing orders can be confirmed.');
        }

        $order->update([
            'status' => 'confirmed',
            'started_at' => now(),
        ]);

        $order->workOrders()
            ->where('status', 'pending')
            ->update(['status' => 'ready']);

        return $order->fresh(['workOrders']);
    }

    public function startWorkOrder(WorkOrder $workOrder): WorkOrder
    {
        if (! in_array($workOrder->status, ['pending', 'ready'], true)) {
            throw new InvalidArgumentException('Work order cannot be started.');
        }

        $workOrder->update([
            'status' => 'progress',
            'started_at' => now(),
        ]);

        return $workOrder->fresh();
    }

    public function completeWorkOrder(WorkOrder $workOrder): WorkOrder
    {
        if ($workOrder->status === 'done') {
            return $workOrder;
        }

        if (! in_array($workOrder->status, ['pending', 'ready', 'progress'], true)) {
            throw new InvalidArgumentException('Work order cannot be completed.');
        }

        $workOrder->update([
            'status' => 'done',
            'started_at' => $workOrder->started_at ?? now(),
            'finished_at' => now(),
        ]);

        return $workOrder->fresh();
    }

    /**
     * @param  array{lot_name?:string|null, lot_id?:int|null}|null  $options
     */
    public function produce(ManufacturingOrder $order, User $user, ?int $qty = null, ?array $options = null): ManufacturingOrder
    {
        if (! $order->isConfirmed() && ! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft or confirmed manufacturing orders can be produced.');
        }

        $order->load(['components', 'workOrders', 'product']);
        $produceQty = $qty ?? ($order->quantity - $order->qty_produced);
        if ($produceQty <= 0) {
            throw new InvalidArgumentException('Nothing left to produce.');
        }
        if ($produceQty > ($order->quantity - $order->qty_produced)) {
            throw new InvalidArgumentException('Cannot produce more than remaining quantity.');
        }

        $openWork = $order->workOrders->filter(fn (WorkOrder $wo) => ! $wo->isDone());
        if ($openWork->isNotEmpty() && $produceQty >= ($order->quantity - $order->qty_produced)) {
            foreach ($openWork as $workOrder) {
                $this->completeWorkOrder($workOrder);
            }
        }

        $ratio = $produceQty / max(1, $order->quantity);
        $stock = $order->source_location_id
            ? Location::query()->findOrFail($order->source_location_id)
            : $this->operations->stockLocation();
        $production = $this->productionLocation();

        return DB::connection('tenant')->transaction(function () use ($order, $user, $produceQty, $ratio, $stock, $production, $options) {
            if ($order->isDraft()) {
                $order->update(['status' => 'confirmed', 'started_at' => now()]);
                $order->workOrders()->where('status', 'pending')->update(['status' => 'ready']);
            }

            $consumeLines = [];
            foreach ($order->components as $component) {
                $need = (int) ceil($component->quantity * $ratio);
                $remaining = $component->quantity - $component->qty_consumed;
                $need = min($need, $remaining);
                if ($need > 0) {
                    $consumeLines[] = [
                        'product_id' => $component->product_id,
                        'quantity' => $need,
                        'uom_id' => $component->uom_id,
                    ];
                }
            }

            if ($consumeLines !== []) {
                $this->operations->createSimpleOperation(
                    type: 'internal',
                    source: $stock,
                    destination: $production,
                    lines: $consumeLines,
                    user: $user,
                    origin: $order->number,
                    manufacturingOrderId: $order->id,
                    notes: 'Component consumption',
                );
            }

            $lotId = $options['lot_id'] ?? null;
            if (! $lotId && ! empty($options['lot_name']) && $order->product?->tracksLots()) {
                $lot = Lot::query()->firstOrCreate(
                    ['product_id' => $order->product_id, 'name' => $options['lot_name']],
                    ['created_by' => $user->id]
                );
                $lotId = $lot->id;
            }

            $this->operations->createSimpleOperation(
                type: 'manufacture',
                source: $production,
                destination: $order->destination_location_id
                    ? Location::query()->findOrFail($order->destination_location_id)
                    : $stock,
                lines: [[
                    'product_id' => $order->product_id,
                    'quantity' => $produceQty,
                    'uom_id' => $order->uom_id,
                    'lot_id' => $lotId,
                ]],
                user: $user,
                origin: $order->number,
                manufacturingOrderId: $order->id,
                notes: 'Finished goods',
            );

            foreach ($order->components as $component) {
                $need = (int) ceil($component->quantity * $ratio);
                $remaining = $component->quantity - $component->qty_consumed;
                $need = min($need, $remaining);
                if ($need > 0) {
                    $component->qty_consumed += $need;
                    $component->save();
                }
            }

            $order->qty_produced += $produceQty;
            $done = $order->qty_produced >= $order->quantity;
            $order->status = $done ? 'done' : 'confirmed';
            if ($done) {
                $order->finished_at = now();
            }
            $order->save();

            return $order->fresh(['product', 'components.product', 'operations', 'workOrders']);
        });
    }

    public function cancel(ManufacturingOrder $order): ManufacturingOrder
    {
        if ($order->isDone() || $order->qty_produced > 0) {
            throw new InvalidArgumentException('Cannot cancel a produced manufacturing order.');
        }

        $order->update(['status' => 'canceled']);
        $order->workOrders()->whereNot('status', 'done')->update(['status' => 'canceled']);

        return $order->fresh();
    }

    /**
     * @param  array{product_id:int, bill_of_material_id?:int|null, manufacturing_order_id?:int|null, quantity:int, lot_id?:int|null, notes?:string|null}  $data
     */
    public function createUnbuild(array $data, User $user): UnbuildOrder
    {
        $product = Product::query()->findOrFail($data['product_id']);
        $bom = null;
        if (! empty($data['bill_of_material_id'])) {
            $bom = BillOfMaterial::query()->with('lines')->findOrFail($data['bill_of_material_id']);
        } elseif (! empty($data['manufacturing_order_id'])) {
            $mo = ManufacturingOrder::query()->with('billOfMaterial.lines')->findOrFail($data['manufacturing_order_id']);
            $bom = $mo->billOfMaterial;
            $data['product_id'] = $mo->product_id;
        } else {
            $bom = BillOfMaterial::query()
                ->with('lines')
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->latest('id')
                ->first();
        }

        if (! $bom || $bom->lines->isEmpty()) {
            throw new InvalidArgumentException('BOM with components is required to unbuild.');
        }

        $stock = $this->operations->stockLocation();

        return UnbuildOrder::query()->create([
            'number' => $this->nextNumber('UB'),
            'status' => 'draft',
            'product_id' => $data['product_id'],
            'bill_of_material_id' => $bom->id,
            'manufacturing_order_id' => $data['manufacturing_order_id'] ?? null,
            'lot_id' => $data['lot_id'] ?? null,
            'quantity' => max(1, (int) $data['quantity']),
            'source_location_id' => $stock->id,
            'destination_location_id' => $stock->id,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
        ]);
    }

    public function validateUnbuild(UnbuildOrder $unbuild, User $user): UnbuildOrder
    {
        if (! $unbuild->isDraft()) {
            throw new InvalidArgumentException('Only draft unbuild orders can be validated.');
        }

        $unbuild->load(['billOfMaterial.lines', 'product']);
        $bom = $unbuild->billOfMaterial;
        if (! $bom) {
            throw new InvalidArgumentException('Unbuild order has no BOM.');
        }

        $qty = max(1, (int) $unbuild->quantity);
        $bomQty = max(1, (int) $bom->quantity);
        $stock = $unbuild->source_location_id
            ? Location::query()->findOrFail($unbuild->source_location_id)
            : $this->operations->stockLocation();
        $production = $this->productionLocation();

        return DB::connection('tenant')->transaction(function () use ($unbuild, $user, $bom, $qty, $bomQty, $stock, $production) {
            $this->operations->createSimpleOperation(
                type: 'unbuild',
                source: $stock,
                destination: $production,
                lines: [[
                    'product_id' => $unbuild->product_id,
                    'quantity' => $qty,
                    'lot_id' => $unbuild->lot_id,
                ]],
                user: $user,
                origin: $unbuild->number,
                notes: 'Unbuild finished goods',
            );

            $returnLines = [];
            foreach ($bom->lines as $line) {
                $returnLines[] = [
                    'product_id' => $line->product_id,
                    'quantity' => (int) ceil(($line->quantity * $qty) / $bomQty),
                    'uom_id' => $line->uom_id,
                ];
            }

            $this->operations->createSimpleOperation(
                type: 'internal',
                source: $production,
                destination: $unbuild->destination_location_id
                    ? Location::query()->findOrFail($unbuild->destination_location_id)
                    : $stock,
                lines: $returnLines,
                user: $user,
                origin: $unbuild->number,
                notes: 'Unbuild return components',
            );

            $unbuild->update([
                'status' => 'done',
                'done_at' => now(),
            ]);

            return $unbuild->fresh(['product', 'billOfMaterial', 'lot']);
        });
    }

    public function productionLocation(): Location
    {
        return Location::query()->firstOrCreate(
            ['code' => 'PRODUCTION', 'warehouse_id' => null],
            ['name' => 'Production', 'type' => 'production', 'is_active' => true]
        );
    }
}
