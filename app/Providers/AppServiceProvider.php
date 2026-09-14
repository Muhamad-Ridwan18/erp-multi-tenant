<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (?User $user, string $ability) {
            if (! $user) {
                return null;
            }

            if ($user->isPlatformAdmin()) {
                return true;
            }

            // Treat ability as permission name when it contains a dot (catalog key).
            if (str_contains($ability, '.')) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        Gate::define('permission', function (User $user, string $permission): bool {
            return $user->hasPermission($permission);
        });
    }
}
