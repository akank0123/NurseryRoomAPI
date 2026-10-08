<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ];
    }
}
