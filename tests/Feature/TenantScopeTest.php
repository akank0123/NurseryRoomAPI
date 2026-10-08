<?php

namespace Tests\Feature;

use App\Exceptions\MissingTenantException;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Tests\TestCase;

/**
 * The model-level scope that every tenant-owned query goes through.
 */
class TenantScopeTest extends TestCase
{
    public function test_queries_only_return_the_current_tenants_records(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $roomA = Room::factory()->for($tenantA)->create();
        Room::factory()->for($tenantB)->create();

        app(TenantContext::class)->set($tenantA);

        $this->assertEquals([$roomA->id], Room::all()->modelKeys());
    }

    public function test_new_records_are_stamped_with_the_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);

        $room = Room::create(['name' => 'Sunflowers']);

        $this->assertSame($tenant->id, $room->tenant_id);
    }

    public function test_tenant_id_cannot_be_mass_assigned(): void
    {
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);

        // Strict mode (non-production) turns the silent discard into an error.
        $this->expectException(MassAssignmentException::class);

        Child::make(['first_name' => 'A', 'last_name' => 'B', 'tenant_id' => $other->id]);
    }

    public function test_querying_without_a_tenant_fails_instead_of_returning_everything(): void
    {
        Room::factory()->create();

        $this->expectException(MissingTenantException::class);

        Room::all();
    }
}
