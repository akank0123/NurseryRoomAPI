<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant from the X-Tenant-ID header.
 *
 * Authentication is out of scope for this exercise, so the header is
 * trusted. In production the tenant would come from the authenticated
 * user, or the header would be checked against the user's memberships.
 */
class IdentifyTenant
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = trim((string) $request->header('X-Tenant-ID'));

        if (! preg_match('/^[a-f0-9]{24}$/i', $header)) {
            return response()->json(['message' => 'A valid X-Tenant-ID header is required.'], 400);
        }

        $tenant = Tenant::find($header);

        if ($tenant === null) {
            return response()->json(['message' => 'Unknown tenant.'], 404);
        }

        $this->context->set($tenant);

        return $next($request);
    }
}
