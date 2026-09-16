<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturingOrderComponent extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'manufacturing_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'qty_consumed',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'qty_consumed' => 'integer',
        ];
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
