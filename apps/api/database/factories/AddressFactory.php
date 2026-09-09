<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
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
