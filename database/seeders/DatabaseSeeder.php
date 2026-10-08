<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Two tenants with a couple of rooms and children each, so the API can be
 * tried by hand (including trying to reach the other tenant's data).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenant('Sunshine Nurseries', ['Sunflowers', 'Little Explorers'], [
            ['Olivia', 'Smith'], ['Noah', 'Jones'], ['Amelia', 'Brown'],
        ]);

        $this->seedTenant('Oak Tree Nurseries', ['Acorns', 'Saplings'], [
            ['Leo', 'Taylor'], ['Isla', 'Wilson'],
        ]);
    }

    private function seedTenant(string $name, array $rooms, array $children): void
    {
        $tenant = Tenant::create(['name' => $name]);

        foreach ($rooms as $room) {
            Room::factory()->for($tenant)->create(['name' => $room]);
        }

        foreach ($children as [$first, $last]) {
            Child::factory()->for($tenant)->create(['first_name' => $first, 'last_name' => $last]);
        }

        $this->printIds($tenant);
    }

    /**
     * Ids are generated ObjectIds, so show them for use in example requests.
     */
    private function printIds(Tenant $tenant): void
    {
        $rows = [['tenant', $tenant->name, $tenant->id]];

        foreach (Room::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get() as $room) {
            $rows[] = ['room', $room->name, $room->id];
        }

        foreach (Child::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get() as $child) {
            $rows[] = ['child', "{$child->first_name} {$child->last_name}", $child->id];
        }

        $this->command?->table(['Type', 'Name', 'Id'], $rows);
    }
}
