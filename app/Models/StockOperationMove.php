<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOperationMove extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'stock_operation_id',
        'product_id',
        'uom_id',
        'lot_id',
        'demand_qty',
        'done_qty',
    ];

    protected function casts(): array
    {
        return [
            'demand_qty' => 'integer',
            'done_qty' => 'integer',
        ];
    }

    public function stockOperation(): BelongsTo
    {
        return $this->belongsTo(StockOperation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
