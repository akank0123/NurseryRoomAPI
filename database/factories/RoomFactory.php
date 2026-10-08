<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['Sunflowers', 'Little Explorers', 'Busy Bees', 'Rainbows']),
        ];
    }
}
