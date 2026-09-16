<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'sku',
        'name',
        'type',
        'barcode',
        'product_category_id',
        'description',
        'unit',
        'uom_id',
        'purchase_uom_id',
        'price',
        'cost',
        'weight',
        'volume',
        'stock_qty',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost' => 'integer',
            'weight' => 'decimal:3',
            'volume' => 'decimal:3',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'purchase_uom_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockQuants(): HasMany
    {
        return $this->hasMany(StockQuant::class);
    }

    public function availableQty(): int
    {
        if ($this->stockQuants()->exists()) {
            return (int) $this->stockQuants()->sum('quantity');
        }

        return (int) $this->stock_qty;
    }

    public function formattedPrice(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }
}
