<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'slug',
        'database',
        'status',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function enabledModuleCodes(): array
    {
        $subscription = $this->activeSubscription()->with('plan.modules')->first();

        if (! $subscription?->plan) {
            return [];
        }

        return $subscription->plan->modules->pluck('code')->all();
    }

    public function domainUrl(string $path = '/'): string
    {
        return \App\Support\TenantContext::tenantUrl($this, $path);
    }
}
