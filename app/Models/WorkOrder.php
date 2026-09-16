<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'manufacturing_order_id',
        'bom_operation_id',
        'work_center_id',
        'name',
        'status',
        'duration_minutes',
        'sort',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'sort' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function bomOperation(): BelongsTo
    {
        return $this->belongsTo(BomOperation::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'ready'], true);
    }
}
