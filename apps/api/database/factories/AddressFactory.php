<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country' => fake()->country(),
            'city' => fake()->city(),
            'street' => fake()->streetName(),
            'area' => fake()->name(),
            'number' => fake()->numberBetween(),
            'complement' => Str::random(1),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'state' => Str::random(2),
        ];
    }
}
