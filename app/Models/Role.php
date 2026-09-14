<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function syncPermissionsWithinPlan(array $permissionIds, array $allowedModuleCodes): void
    {
        $validIds = Permission::query()
            ->whereIn('id', $permissionIds)
            ->whereIn('module_code', $allowedModuleCodes)
            ->pluck('id')
            ->all();

        $this->permissions()->sync($validIds);
    }
}
