<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatement extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'reference',
        'journal_id',
        'date',
        'balance_start',
        'balance_end',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'balance_start' => 'integer',
            'balance_end' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class)->orderBy('sort');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function reconciledCount(): int
    {
        return $this->lines()->where('is_reconciled', true)->count();
    }
}
