<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'days',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
