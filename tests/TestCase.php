<?php

namespace Tests;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Fresh collections and indexes per test. RefreshDatabase would wrap
    // each test in a transaction, which MongoDB only supports on replica sets.
    use DatabaseMigrations;

    /** A well-formed id that matches no document. */
    protected const MISSING_ID = '000000000000000000000000';

    /**
     * Send the following requests on behalf of the given tenant.
     */
    protected function asTenant(Tenant $tenant): static
    {
        return $this->withHeader('X-Tenant-ID', (string) $tenant->id);
    }

    /**
     * Put a child in a room directly, bypassing the API.
     */
    protected function openCheckIn(Room $room, Child $child): CheckIn
    {
        return CheckIn::forceCreate([
            'tenant_id' => $room->tenant_id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_in_at' => now(),
        ]);
    }

    /**
     * Read a check-in without the tenant scope, to assert on raw state.
     */
    protected function findCheckIn(string $id): ?CheckIn
    {
        return CheckIn::withoutGlobalScopes()->find($id);
    }
}
