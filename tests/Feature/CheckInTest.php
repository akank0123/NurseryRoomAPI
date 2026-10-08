<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use MongoDB\Driver\Exception\BulkWriteException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    private Tenant $tenant;

    private Room $sunflowers;

    private Room $explorers;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->sunflowers = Room::factory()->for($this->tenant)->create(['name' => 'Sunflowers']);
        $this->explorers = Room::factory()->for($this->tenant)->create(['name' => 'Little Explorers']);
        $this->child = Child::factory()->for($this->tenant)->create();
    }

    public function test_it_checks_a_child_into_a_room(): void
    {
        $this->freezeSecond();

        $response = $this->asTenant($this->tenant)
            ->postJson("/rooms/{$this->sunflowers->id}/check-ins", ['child_id' => $this->child->id]);

        $response->assertCreated()
            ->assertJsonPath('data.room_id', $this->sunflowers->id)
            ->assertJsonPath('data.child_id', $this->child->id)
            ->assertJsonPath('data.checked_in_at', now()->toIso8601String())
            ->assertJsonPath('data.checked_out_at', null);

        $this->assertDatabaseHas('check_ins', [
            'id' => $response->json('data.id'),
            'tenant_id' => $this->tenant->id,
            'room_id' => $this->sunflowers->id,
            'child_id' => $this->child->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_it_rejects_checking_a_child_into_a_second_room(): void
    {
        $existing = $this->openCheckIn($this->sunflowers, $this->child);

        $this->asTenant($this->tenant)
            ->postJson("/rooms/{$this->explorers->id}/check-ins", ['child_id' => $this->child->id])
            ->assertConflict()
            ->assertJsonPath('current_check_in.id', $existing->id)
            ->assertJsonPath('current_check_in.room.id', $this->sunflowers->id)
            ->assertJsonPath('current_check_in.room.name', 'Sunflowers');

        // The child stays where they were; nothing is moved or created.
        $this->assertDatabaseCount('check_ins', 1);
        $this->assertNull($this->findCheckIn($existing->id)->checked_out_at);
    }

    public function test_it_rejects_checking_a_child_into_the_same_room_twice(): void
    {
        $this->openCheckIn($this->sunflowers, $this->child);

        $this->asTenant($this->tenant)
            ->postJson("/rooms/{$this->sunflowers->id}/check-ins", ['child_id' => $this->child->id])
            ->assertConflict();

        $this->assertDatabaseCount('check_ins', 1);
    }

    public function test_a_child_can_be_checked_in_again_after_checking_out(): void
    {
        $previous = $this->openCheckIn($this->sunflowers, $this->child);
        $previous->forceFill(['checked_out_at' => now()])->save();

        $this->asTenant($this->tenant)
            ->postJson("/rooms/{$this->explorers->id}/check-ins", ['child_id' => $this->child->id])
            ->assertCreated();

        $this->assertDatabaseCount('check_ins', 2);
    }

    public function test_it_returns_not_found_for_an_unknown_room(): void
    {
        $this->asTenant($this->tenant)
            ->postJson('/rooms/'.self::MISSING_ID.'/check-ins', ['child_id' => $this->child->id])
            ->assertNotFound();
    }

    public function test_it_rejects_an_unknown_child(): void
    {
        $this->asTenant($this->tenant)
            ->postJson("/rooms/{$this->sunflowers->id}/check-ins", ['child_id' => self::MISSING_ID])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('child_id');
    }

    #[DataProvider('invalidChildIds')]
    public function test_it_validates_the_child_id(mixed $childId): void
    {
        $this->asTenant($this->tenant)
            ->postJson("/rooms/{$this->sunflowers->id}/check-ins", ['child_id' => $childId])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('child_id');

        $this->assertDatabaseCount('check_ins', 0);
    }

    public static function invalidChildIds(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'not an id' => ['abc'],
            'integer' => [42],
            'query operator' => [['$ne' => null]],
        ];
    }

    public function test_the_database_refuses_a_second_open_check_in_for_a_child(): void
    {
        // Guards against two requests racing past the application-level check.
        $this->openCheckIn($this->sunflowers, $this->child);

        $this->expectException(BulkWriteException::class);

        $this->openCheckIn($this->explorers, $this->child);
    }
}
