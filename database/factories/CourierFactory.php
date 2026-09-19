<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('081#########'),
            'email' => fake()->unique()->safeEmail(),
            'id_card_number' => fake()->unique()->numerify('################'),
            'address' => fake()->address(),
            'vehicle_type' => fake()->randomElement(array_column(VehicleType::cases(), 'value')),
            'vehicle_plate' => fake()->bothify('B #### ??'),
            'level' => fake()->numberBetween(Courier::MIN_LEVEL, Courier::MAX_LEVEL),
            'is_active' => true,
        ];
    }
}
