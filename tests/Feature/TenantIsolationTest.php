<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Tests\TestCase;

/**
 * A request made for one tenant must never read or change another
 * tenant's data. Every case here acts as tenant A against tenant B's data,
 * and B's data must look exactly as if it did not exist.
 */
class TenantIsolationTest extends TestCase
{
    private Tenant $tenantA;

    private Room $roomA;

    private Child $childA;

    private Tenant $tenantB;

    private Room $roomB;

    private Child $childB;

    private CheckIn $checkInB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create();
        $this->roomA = Room::factory()->for($this->tenantA)->create();
        $this->childA = Child::factory()->for($this->tenantA)->create();

        $this->tenantB = Tenant::factory()->create();
        $this->roomB = Room::factory()->for($this->tenantB)->create();
        $this->childB = Child::factory()->for($this->tenantB)->create();
        $this->checkInB = $this->openCheckIn($this->roomB, $this->childB);
    }

    public function test_tenant_a_cannot_list_occupants_of_tenant_bs_room(): void
    {
        $this->asTenant($this->tenantA)
            ->getJson("/rooms/{$this->roomB->id}/occupants")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.']);
    }

    public function test_tenant_a_cannot_check_out_tenant_bs_child(): void
    {
        $this->asTenant($this->tenantA)
            ->postJson("/check-ins/{$this->checkInB->id}/check-out")
            ->assertNotFound();

        $this->assertNull($this->findCheckIn($this->checkInB->id)->checked_out_at);

        // And tenant B still sees the child in the room.
        $this->asTenant($this->tenantB)
            ->getJson("/rooms/{$this->roomB->id}/occupants")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.child.id', $this->childB->id);
    }

    public function test_tenant_a_cannot_check_a_child_into_tenant_bs_room(): void
    {
        $this->asTenant($this->tenantA)
            ->postJson("/rooms/{$this->roomB->id}/check-ins", ['child_id' => $this->childA->id])
            ->assertNotFound();

        $this->assertDatabaseMissing('check_ins', ['child_id' => $this->childA->id]);
    }

    public function test_tenant_a_cannot_check_tenant_bs_child_into_its_own_room(): void
    {
        $other = Child::factory()->for($this->tenantB)->create();

        // Same response as for a child that does not exist at all.
        $this->asTenant($this->tenantA)
            ->postJson("/rooms/{$this->roomA->id}/check-ins", ['child_id' => $other->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['child_id' => 'The selected child does not exist.']);

        $this->assertDatabaseMissing('check_ins', ['child_id' => $other->id]);
    }

    public function test_a_conflict_does_not_reveal_another_tenants_check_in(): void
    {
        // childB is checked in at tenant B. Tenant A asking about that child
        // must get "does not exist", not a 409 pointing at tenant B's room.
        $this->asTenant($this->tenantA)
            ->postJson("/rooms/{$this->roomA->id}/check-ins", ['child_id' => $this->childB->id])
            ->assertUnprocessable()
            ->assertJsonMissingPath('current_check_in');
    }

    public function test_occupants_only_include_the_requesting_tenants_children(): void
    {
        $this->openCheckIn($this->roomA, $this->childA);

        $this->asTenant($this->tenantA)
            ->getJson("/rooms/{$this->roomA->id}/occupants")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.child.id', $this->childA->id);
    }
}
