<?php

namespace App\Support;

use App\Models\Journal;
use App\Models\PaymentTerm;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\Uom;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Master data lookups for form dropdowns. Tenants provisioned before the
 * parity migration may not have these tables yet, so every lookup degrades
 * to an empty collection instead of failing the request.
 */
class TenantMasterData
{
    public static function paymentTerms(): Collection
    {
        return self::fetch('payment_terms', fn () => PaymentTerm::query()->where('is_active', true)->orderBy('days')->get());
    }

    public static function warehouses(): Collection
    {
        return self::fetch('warehouses', fn () => Warehouse::query()->where('is_active', true)->orderBy('code')->get());
    }

    public static function taxes(): Collection
    {
        return self::fetch('taxes', fn () => Tax::query()->where('is_active', true)->orderBy('name')->get());
    }

    public static function uoms(): Collection
    {
        return self::fetch('uoms', fn () => Uom::query()->where('is_active', true)->orderBy('name')->get());
    }

    public static function journals(): Collection
    {
        return self::fetch('journals', fn () => Journal::query()->where('is_active', true)->orderBy('code')->get());
    }

    public static function productCategories(): Collection
    {
        return self::fetch('product_categories', fn () => ProductCategory::query()->orderBy('name')->get());
    }

    /**
     * @param  callable(): Collection  $query
     */
    protected static function fetch(string $table, callable $query): Collection
    {
        if (! Schema::connection('tenant')->hasTable($table)) {
            return new Collection;
        }

        return $query();
    }
}
