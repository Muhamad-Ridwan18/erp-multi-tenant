<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'kind',
        'customer_id',
        'currency_id',
        'payment_term_id',
        'warehouse_id',
        'validity_date',
        'status',
        'delivery_status',
        'invoice_status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'notes',
        'terms',
        'ordered_at',
        'commitment_date',
        'client_order_ref',
        'origin',
        'confirmed_at',
        'created_by',
        'salesperson_id',
        'quotation_id',
    ];

    protected function casts(): array
    {
        return [
            'validity_date' => 'date',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
            'ordered_at' => 'datetime',
            'commitment_date' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'quotation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function stockOperations(): HasMany
    {
        return $this->hasMany(StockOperation::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(StockOperation::class)->where('type', 'delivery');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isQuotation(): bool
    {
        return $this->kind === 'quotation';
    }

    public function isSale(): bool
    {
        return $this->kind === 'order';
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
        if ($this->invoice?->isPaid()) {
            return 'paid';
        }

        if ($this->invoice) {
            return 'invoiced';
        }

        return $this->status;
    }
}
