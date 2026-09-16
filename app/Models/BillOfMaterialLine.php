<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterialLine extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'bill_of_material_id',
        'product_id',
        'uom_id',
        'quantity',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
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
