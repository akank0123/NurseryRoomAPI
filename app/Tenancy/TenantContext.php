<?php

namespace App\Tenancy;

use App\Exceptions\MissingTenantException;
use App\Models\Tenant;

/**
 * Holds the tenant for the current request.
 *
 * Registered as a scoped singleton so it is reset between requests (and
 * between Octane/queue jobs) and can never leak from one tenant to another.
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function id(): string
    {
        if ($this->tenant === null) {
            throw new MissingTenantException;
        }

        return $this->tenant->id;
    }
}
