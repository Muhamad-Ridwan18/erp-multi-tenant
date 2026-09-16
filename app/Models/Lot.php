<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'reference',
        'product_id',
        'expiration_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockQuants(): HasMany
    {
        return $this->hasMany(StockQuant::class);
    }
}
