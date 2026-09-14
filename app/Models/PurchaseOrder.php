<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'vendor_id',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'notes',
        'terms',
        'confirmed_at',
        'received_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
            'confirmed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function formattedSubtotal(): string
    {
        return 'Rp '.number_format($this->subtotal, 0, ',', '.');
    }

    public function formattedGrandTotal(): string
    {
        $amount = $this->grand_total ?: $this->subtotal;

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    public function progressStatus(): string
    {
        if ($this->bill?->isPaid()) {
            return 'paid';
        }

        if ($this->bill) {
            return 'billed';
        }

        return $this->status;
    }
}
