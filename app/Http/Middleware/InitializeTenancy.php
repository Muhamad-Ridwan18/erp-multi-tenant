<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function __construct(protected TenantDatabaseManager $databases) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->databases->disconnect();

        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        $slug = $this->extractTenantSlug($host, $centralDomains);

        if ($slug === null) {
            abort(404, 'Unknown host.');
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        if ($tenant->status === 'suspended') {
            abort(403, 'This tenant is suspended.');
        }

        $this->databases->connect($tenant);

        return $next($request);
    }

    /**
     * @param  list<string>  $centralDomains
     */
    protected function extractTenantSlug(string $host, array $centralDomains): ?string
    {
        foreach ($centralDomains as $central) {
            $suffix = '.'.$central;
            if (! str_ends_with($host, $suffix)) {
                continue;
            }

            $slug = substr($host, 0, -strlen($suffix));

            if ($slug === '' || str_contains($slug, '.')) {
                return null;
            }

            return $slug;
        }

        return null;
    }
}
