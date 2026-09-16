<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'bank_statement_id',
        'date',
        'payment_reference',
        'partner_name',
        'label',
        'amount',
        'is_reconciled',
        'payment_id',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
            'is_reconciled' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
