<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'code',
        'name',
        'notes',
        'default_capacity',
        'costs_per_hour',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_capacity' => 'integer',
            'costs_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function billsOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class);
    }

    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class);
    }
}
