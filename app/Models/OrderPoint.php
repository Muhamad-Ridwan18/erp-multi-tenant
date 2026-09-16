<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPoint extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'product_id',
        'location_id',
        'min_qty',
        'max_qty',
        'trigger',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_qty' => 'integer',
            'max_qty' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function onHand(): int
    {
        $query = StockQuant::query()->where('product_id', $this->product_id);

        if ($this->location_id) {
            $query->where('location_id', $this->location_id);
        } else {
            $internal = Location::query()->where('type', 'internal')->pluck('id');
            $query->whereIn('location_id', $internal);
        }

        return (int) $query->sum('quantity');
    }

    public function isBelowMin(): bool
    {
        return $this->onHand() < $this->min_qty;
    }

    public function suggestedQty(): int
    {
        $target = $this->max_qty > 0 ? $this->max_qty : $this->min_qty;
        $need = $target - $this->onHand();

        return max(0, $need);
    }
}
