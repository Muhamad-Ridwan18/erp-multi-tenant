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
        'partner_reference',
        'currency_id',
        'payment_term_id',
        'destination_location_id',
        'status',
        'receipt_status',
        'billing_status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'notes',
        'terms',
        'ordered_at',
        'planned_at',
        'origin',
        'confirmed_at',
        'received_at',
        'created_by',
        'buyer_id',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
            'ordered_at' => 'datetime',
            'planned_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function stockOperations(): HasMany
    {
        return $this->hasMany(StockOperation::class);
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

    public function isCanceled(): bool
    {
        return $this->status === 'canceled' || $this->status === 'cancelled';
    }

    public function isPurchase(): bool
    {
        return in_array($this->status, ['confirmed', 'purchase'], true);
    }

    public function updateReceiptStatus(): void
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            $this->update(['receipt_status' => 'no']);

            return;
        }

        $allReceived = $items->every(fn (PurchaseOrderItem $item) => $item->qty_received >= $item->quantity);
        $anyReceived = $items->contains(fn (PurchaseOrderItem $item) => $item->qty_received > 0);

        $this->update([
            'receipt_status' => $allReceived ? 'full' : ($anyReceived ? 'partial' : 'no'),
        ]);
    }

    public function updateBillingStatus(): void
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            $this->update(['billing_status' => 'no']);

            return;
        }

        $allInvoiced = $items->every(fn (PurchaseOrderItem $item) => $item->qty_invoiced >= $item->quantity);
        $anyInvoiced = $items->contains(fn (PurchaseOrderItem $item) => $item->qty_invoiced > 0);

        $this->update([
            'billing_status' => $allInvoiced ? 'invoiced' : ($anyInvoiced ? 'partial' : 'no'),
        ]);
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
