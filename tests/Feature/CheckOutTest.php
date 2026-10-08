<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Tests\TestCase;

class CheckOutTest extends TestCase
{
    private Tenant $tenant;

    private Room $room;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->room = Room::factory()->for($this->tenant)->create();
        $this->child = Child::factory()->for($this->tenant)->create();
    }

    public function test_it_checks_a_child_out(): void
    {
        $checkIn = $this->openCheckIn($this->room, $this->child);
        $this->freezeSecond();

        $this->asTenant($this->tenant)
            ->postJson("/check-ins/{$checkIn->id}/check-out")
            ->assertOk()
            ->assertJsonPath('data.id', $checkIn->id)
            ->assertJsonPath('data.checked_out_at', now()->toIso8601String());

        $this->assertNotNull($this->findCheckIn($checkIn->id)->checked_out_at);
    }

    public function test_checking_out_twice_is_a_conflict_and_keeps_the_original_time(): void
    {
        $checkIn = $this->openCheckIn($this->room, $this->child);
        $this->freezeSecond();

        $this->asTenant($this->tenant)->postJson("/check-ins/{$checkIn->id}/check-out")->assertOk();
        $firstCheckOut = $this->findCheckIn($checkIn->id)->checked_out_at;

        $this->travel(10)->minutes();

        $this->asTenant($this->tenant)
            ->postJson("/check-ins/{$checkIn->id}/check-out")
            ->assertConflict();

        $this->assertEquals($firstCheckOut, $this->findCheckIn($checkIn->id)->checked_out_at);
    }

    public function test_it_returns_not_found_for_an_unknown_check_in(): void
    {
        $this->asTenant($this->tenant)
            ->postJson('/check-ins/'.self::MISSING_ID.'/check-out')
            ->assertNotFound();
    }
}
