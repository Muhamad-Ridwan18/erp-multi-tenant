<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends Model
{
    protected $connection = 'tenant';

    protected $table = 'bills_of_materials';

    protected $fillable = [
        'code',
        'product_id',
        'uom_id',
        'quantity',
        'work_center_id',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class)->orderBy('sort');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BomOperation::class)->orderBy('sort');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class);
    }
}
