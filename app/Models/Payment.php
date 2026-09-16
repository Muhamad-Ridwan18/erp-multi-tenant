<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'number',
        'direction',
        'invoice_id',
        'bill_id',
        'journal_id',
        'currency_id',
        'currency_rate',
        'amount',
        'amount_company',
        'paid_at',
        'notes',
        'memo',
        'is_reconciled',
        'bank_statement_line_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'amount_company' => 'integer',
            'currency_rate' => 'decimal:6',
            'is_reconciled' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formattedAmount(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }
}
