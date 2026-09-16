<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomOperation extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'bill_of_material_id',
        'work_center_id',
        'name',
        'duration_minutes',
        'sort',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }
}
