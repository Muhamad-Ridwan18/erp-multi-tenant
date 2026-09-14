<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $starter = Plan::query()->updateOrCreate(
            ['code' => 'starter'],
            [
                'name' => 'Starter',
                'price_monthly' => 299000,
                'is_active' => true,
            ]
        );

        $business = Plan::query()->updateOrCreate(
            ['code' => 'business'],
            [
                'name' => 'Business',
                'price_monthly' => 799000,
                'is_active' => true,
            ]
        );

        $starterModules = Module::query()
            ->whereIn('code', ['sales', 'settings'])
            ->pluck('id');

        $businessModules = Module::query()->pluck('id');

        $starter->modules()->sync($starterModules);
        $business->modules()->sync($businessModules);
    }
}
