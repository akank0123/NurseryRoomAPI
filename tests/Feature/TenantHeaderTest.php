<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Tenant;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TenantHeaderTest extends TestCase
{
    public function test_requests_without_a_tenant_header_are_rejected(): void
    {
        $room = Room::factory()->create();

        $this->getJson("/rooms/{$room->id}/occupants")
            ->assertBadRequest()
            ->assertJsonPath('message', 'A valid X-Tenant-ID header is required.');
    }

    #[DataProvider('malformedHeaders')]
    public function test_malformed_tenant_headers_are_rejected(string $header): void
    {
        $room = Room::factory()->create();

        $this->withHeader('X-Tenant-ID', $header)
            ->getJson("/rooms/{$room->id}/occupants")
            ->assertBadRequest();
    }

    public static function malformedHeaders(): array
    {
        return [
            'empty' => [''],
            'not an id' => ['abc'],
            'integer' => ['42'],
            'query operator' => ['{"$ne":null}'],
        ];
    }

    public function test_an_unknown_tenant_is_rejected(): void
    {
        $this->withHeader('X-Tenant-ID', self::MISSING_ID)
            ->getJson('/rooms/'.self::MISSING_ID.'/occupants')
            ->assertNotFound()
            ->assertJsonPath('message', 'Unknown tenant.');
    }

    public function test_the_header_is_checked_on_every_endpoint(): void
    {
        $this->postJson('/rooms/'.self::MISSING_ID.'/check-ins', ['child_id' => self::MISSING_ID])->assertBadRequest();
        $this->postJson('/check-ins/'.self::MISSING_ID.'/check-out')->assertBadRequest();
    }

    public function test_surrounding_whitespace_in_the_header_is_ignored(): void
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->for($tenant)->create();

        $this->withHeader('X-Tenant-ID', " {$tenant->id} ")
            ->getJson("/rooms/{$room->id}/occupants")
            ->assertOk();
    }

    public function test_a_valid_header_selects_the_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->for($tenant)->create();

        $this->asTenant($tenant)
            ->getJson("/rooms/{$room->id}/occupants")
            ->assertOk();
    }
}
