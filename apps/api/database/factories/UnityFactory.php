<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unity>
 */
class UnityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'abbreviation' => fake()->word(),
            'name' => fake()->word(),
            'dimension' => fake()->randomElement(['mass', 'volume', 'unit']),
            'convertion_factor' => fake()->randomElement([1, 10, 100, 1000]),
            'base_unity_id' => null,
        ];
    }
}
