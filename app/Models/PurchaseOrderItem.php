<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'qty_received',
        'qty_invoiced',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'tax_id',
        'line_total',
        'planned_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'qty_received' => 'integer',
            'qty_invoiced' => 'integer',
            'unit_price' => 'integer',
            'discount_percent' => 'integer',
            'tax_percent' => 'integer',
            'line_total' => 'integer',
            'planned_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
