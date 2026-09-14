<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach (config('permissions.modules') as $code => $name) {
            Module::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => "{$name} module",
                    'sort' => $sort++,
                ]
            );
        }
    }
}
