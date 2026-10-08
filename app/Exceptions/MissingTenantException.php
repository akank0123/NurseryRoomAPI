<?php

namespace App\Exceptions;

use LogicException;

/**
 * Thrown when tenant-owned data is touched without a tenant in context.
 *
 * This is a programming error, not a client error: we would rather fail
 * loudly than run an unscoped query across every tenant's data.
 */
class MissingTenantException extends LogicException
{
    public function __construct()
    {
        parent::__construct('No tenant has been set for this request.');
    }
}
