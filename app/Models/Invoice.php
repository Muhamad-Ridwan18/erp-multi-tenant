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
        'move_type',
        'customer_id',
        'sales_order_id',
        'reversed_invoice_id',
        'invoice_date',
        'due_date',
        'payment_term_id',
        'journal_id',
        'currency_id',
        'reference',
        'status',
        'payment_state',
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
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
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

    public function reversedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_invoice_id');
    }

    public function isCreditNote(): bool
    {
        return $this->move_type === 'out_refund';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
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
