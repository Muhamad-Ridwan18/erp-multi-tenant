<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $user = $request->user();

        if ($user?->tenant_id && $user->relationLoaded('tenant')) {
            TenantContext::set($user->tenant);
        } elseif ($user?->tenant_id) {
            TenantContext::set($user->tenant()->first());
        }

        return $next($request);
    }
}
