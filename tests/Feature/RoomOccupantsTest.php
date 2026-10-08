<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Tests\TestCase;

class RoomOccupantsTest extends TestCase
{
    private Tenant $tenant;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->room = Room::factory()->for($this->tenant)->create();
    }

    public function test_it_lists_children_currently_in_the_room(): void
    {
        $olivia = Child::factory()->for($this->tenant)->create(['first_name' => 'Olivia']);
        $noah = Child::factory()->for($this->tenant)->create(['first_name' => 'Noah']);

        $oliviaCheckIn = $this->openCheckIn($this->room, $olivia);
        $this->travel(5)->minutes();
        $this->openCheckIn($this->room, $noah);

        $this->asTenant($this->tenant)
            ->getJson("/rooms/{$this->room->id}/occupants")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            // Ordered by arrival.
            ->assertJsonPath('data.0.child.first_name', 'Olivia')
            ->assertJsonPath('data.0.check_in_id', $oliviaCheckIn->id)
            ->assertJsonPath('data.1.child.first_name', 'Noah');
    }

    public function test_it_excludes_children_who_have_checked_out(): void
    {
        $stayed = Child::factory()->for($this->tenant)->create();
        $left = Child::factory()->for($this->tenant)->create();

        $this->openCheckIn($this->room, $stayed);
        $this->openCheckIn($this->room, $left)->forceFill(['checked_out_at' => now()])->save();

        $this->asTenant($this->tenant)
            ->getJson("/rooms/{$this->room->id}/occupants")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.child.id', $stayed->id);
    }

    public function test_it_excludes_children_in_other_rooms(): void
    {
        $otherRoom = Room::factory()->for($this->tenant)->create();
        $this->openCheckIn($otherRoom, Child::factory()->for($this->tenant)->create());

        $this->asTenant($this->tenant)
            ->getJson("/rooms/{$this->room->id}/occupants")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_an_empty_room_returns_an_empty_list(): void
    {
        $this->asTenant($this->tenant)
            ->getJson("/rooms/{$this->room->id}/occupants")
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_it_returns_not_found_for_an_unknown_room(): void
    {
        $this->asTenant($this->tenant)
            ->getJson('/rooms/'.self::MISSING_ID.'/occupants')
            ->assertNotFound();
    }
}
