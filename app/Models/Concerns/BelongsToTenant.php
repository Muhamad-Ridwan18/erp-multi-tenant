<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if (! $model->getAttribute('tenant_id') && TenantContext::id()) {
                $model->setAttribute('tenant_id', TenantContext::id());
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (TenantContext::id()) {
                $builder->where(
                    $builder->getModel()->getTable().'.tenant_id',
                    TenantContext::id()
                );
            }
        });
    }
}
