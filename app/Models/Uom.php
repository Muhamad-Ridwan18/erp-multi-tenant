<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Uom extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'uom_category_id',
        'name',
        'code',
        'ratio',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ratio' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(UomCategory::class, 'uom_category_id');
    }
}
