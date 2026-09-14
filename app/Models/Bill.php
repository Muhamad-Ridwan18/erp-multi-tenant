<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'vendor_id',
        'purchase_order_id',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'amount_paid',
        'notes',
        'terms',
        'posted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
            'amount_paid' => 'integer',
            'posted_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function amountDue(): int
    {
        $total = $this->grand_total ?: $this->subtotal;

        return max(0, $total - $this->amount_paid);
    }

    public function isPaid(): bool
    {
        return $this->isPosted() && $this->amountDue() === 0;
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

    public function formattedAmountDue(): string
    {
        return 'Rp '.number_format($this->amountDue(), 0, ',', '.');
    }
}
