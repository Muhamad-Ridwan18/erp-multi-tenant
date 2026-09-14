<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['tenant_id', 'name', 'email', 'password', 'is_platform_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin && $this->tenant_id === null;
    }

    public function permissionNames(): Collection
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->loadMissing('permissions')
                ->flatMap(fn (Role $role) => $role->permissions)
                ->pluck('name')
                ->unique()
                ->values();
        }

        return Permission::query()
            ->whereHas('roles.users', fn ($q) => $q->where('users.id', $this->id))
            ->pluck('name');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->permissionNames()->contains($permission);
    }
}
