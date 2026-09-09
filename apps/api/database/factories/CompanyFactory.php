<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\odel=Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'website' => fake()->domainWord(),
            'cnpj' => fake()->numberBetween(11111111111, 99999999999),
            'email' => fake()->email(),
            'status' => fake()->randomElement(Company::VALID_STATUSES),
            'phone' => fake()->phoneNumber(),
            'raw_address' => fake()->address(),
            'ie' => fake()->randomNumber(9),
        ];
    }
}
