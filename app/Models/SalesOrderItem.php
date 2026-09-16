<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'qty_delivered',
        'qty_invoiced',
        'customer_lead',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'tax_id',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'qty_delivered' => 'integer',
            'qty_invoiced' => 'integer',
            'customer_lead' => 'integer',
            'unit_price' => 'integer',
            'discount_percent' => 'integer',
            'tax_percent' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
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
