<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('permissions.permissions') as $item) {
            $name = "{$item['module']}.{$item['resource']}.{$item['action']}";

            Permission::query()->updateOrCreate(
                ['name' => $name],
                [
                    'module_code' => $item['module'],
                    'resource' => $item['resource'],
                    'action' => $item['action'],
                    'description' => $item['description'] ?? null,
                ]
            );
        }
    }
}
