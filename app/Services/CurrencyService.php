<?php

namespace App\Services;

use App\Models\Currency;

class CurrencyService
{
    public function companyCurrency(): Currency
    {
        return Currency::query()->where('code', 'IDR')->firstOrFail();
    }

    public function rateFor(?int $currencyId): float
    {
        if (! $currencyId) {
            return 1.0;
        }

        $rate = (float) Currency::query()->whereKey($currencyId)->value('rate');

        return $rate > 0 ? $rate : 1.0;
    }

    /**
     * Convert foreign amount (minor units) to company currency (IDR) using rate
     * where rate = company units per 1 foreign unit (e.g. USD rate 16000 means 1 USD = 16000 IDR).
     */
    public function toCompany(int $amount, float $rate): int
    {
        return (int) round($amount * max($rate, 0.000001));
    }

    public function snapshot(?int $currencyId): array
    {
        $rate = $this->rateFor($currencyId);

        return [
            'currency_id' => $currencyId,
            'currency_rate' => $rate,
        ];
    }
}
