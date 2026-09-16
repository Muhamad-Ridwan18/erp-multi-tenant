<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOperation extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'type',
        'status',
        'source_location_id',
        'destination_location_id',
        'partner_customer_id',
        'partner_vendor_id',
        'sales_order_id',
        'purchase_order_id',
        'manufacturing_order_id',
        'origin',
        'notes',
        'scheduled_at',
        'done_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'partner_customer_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'partner_vendor_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function moves(): HasMany
    {
        return $this->hasMany(StockOperationMove::class);
    }
}
