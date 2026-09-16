<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\Location;
use App\Models\ManufacturingOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManufacturingService
{
    public function __construct(protected InventoryOperationService $operations) {}

    public function nextNumber(): string
    {
        $prefix = 'MO-'.now()->format('Ymd').'-';
        $latest = ManufacturingOrder::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');
        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array{product_id:int, bill_of_material_id?:int|null, work_center_id?:int|null, quantity:int, origin?:string|null, notes?:string|null, scheduled_at?:string|null}  $data
     */
    public function create(array $data, User $user): ManufacturingOrder
    {
        $bom = null;
        if (! empty($data['bill_of_material_id'])) {
            $bom = BillOfMaterial::query()->with('lines')->findOrFail($data['bill_of_material_id']);
            if ((int) $bom->product_id !== (int) $data['product_id']) {
                throw new InvalidArgumentException('BOM product does not match manufacturing order product.');
            }
        } else {
            $bom = BillOfMaterial::query()
                ->with('lines')
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
        $production = $this->productionLocation();

        return DB::connection('tenant')->transaction(function () use ($data, $user, $bom, $qty, $bomQty, $stock) {
            $order = ManufacturingOrder::query()->create([
                'number' => $this->nextNumber(),
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

            return $order->fresh(['product', 'components.product', 'billOfMaterial', 'workCenter']);
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

        return $order->fresh();
    }

    public function produce(ManufacturingOrder $order, User $user, ?int $qty = null): ManufacturingOrder
    {
        if (! $order->isConfirmed() && ! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft or confirmed manufacturing orders can be produced.');
        }

        $order->load('components');
        $produceQty = $qty ?? ($order->quantity - $order->qty_produced);
        if ($produceQty <= 0) {
            throw new InvalidArgumentException('Nothing left to produce.');
        }
        if ($produceQty > ($order->quantity - $order->qty_produced)) {
            throw new InvalidArgumentException('Cannot produce more than remaining quantity.');
        }

        $ratio = $produceQty / max(1, $order->quantity);
        $stock = $order->source_location_id
            ? Location::query()->findOrFail($order->source_location_id)
            : $this->operations->stockLocation();
        $production = $this->productionLocation();

        return DB::connection('tenant')->transaction(function () use ($order, $user, $produceQty, $ratio, $stock, $production) {
            if ($order->isDraft()) {
                $order->update(['status' => 'confirmed', 'started_at' => now()]);
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

            return $order->fresh(['product', 'components.product', 'operations']);
        });
    }

    public function cancel(ManufacturingOrder $order): ManufacturingOrder
    {
        if ($order->isDone() || $order->qty_produced > 0) {
            throw new InvalidArgumentException('Cannot cancel a produced manufacturing order.');
        }

        $order->update(['status' => 'canceled']);

        return $order->fresh();
    }

    public function productionLocation(): Location
    {
        return Location::query()->firstOrCreate(
            ['code' => 'PRODUCTION', 'warehouse_id' => null],
            ['name' => 'Production', 'type' => 'production', 'is_active' => true]
        );
    }
}
