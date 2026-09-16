<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxGroup extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
    ];

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }
}
