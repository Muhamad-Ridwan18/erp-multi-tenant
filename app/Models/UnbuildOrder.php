<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnbuildOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'status',
        'product_id',
        'bill_of_material_id',
        'manufacturing_order_id',
        'lot_id',
        'quantity',
        'source_location_id',
        'destination_location_id',
        'notes',
        'done_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'done_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }
}
