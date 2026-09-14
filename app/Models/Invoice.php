<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'customer_id',
        'sales_order_id',
        'status',
        'subtotal',
        'amount_paid',
        'notes',
        'posted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'amount_paid' => 'integer',
            'posted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
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
        return max(0, $this->subtotal - $this->amount_paid);
    }

    public function isPaid(): bool
    {
        return $this->isPosted() && $this->amountDue() === 0;
    }

    public function formattedSubtotal(): string
    {
        return 'Rp '.number_format($this->subtotal, 0, ',', '.');
    }

    public function formattedAmountDue(): string
    {
        return 'Rp '.number_format($this->amountDue(), 0, ',', '.');
    }
}
