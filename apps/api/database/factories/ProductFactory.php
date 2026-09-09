<?php

namespace Database\Factories;

use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Models\ProductCategory;
use App\Models\Unity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unity = Unity::factory()->create();
        $name = fake()->words(3, true);
        $quantitySource = ProductQuantitySource::DefaultExtraction;

        return [
            'unit_id' => $unity->id,
            'quantity' => fake()->numberBetween(1, 10),
            'name' => $name,
            'raw_name' => $name,
            'normalized_name' => strtolower(Str::ascii($name)),
            'search_description' => strtolower(Str::ascii($name)),
            'normalized_quantity' => fake()->randomFloat(3, 1, 1000),
            'quantity_dimension' => $unity->dimension,
            'quantity_source' => $quantitySource->value,
            'quantity_confidence' => $quantitySource->confidence(),
            'refined' => ProductRefinementStatus::Unrefined->value,
            'sku' => fake()->numberBetween(1, 999999),
            'average_price' => fake()->randomFloat(2, 1, 1000),
            'category_id' => ProductCategory::factory()->create()->id,
            'ean' => fake()->numberBetween(1, 999999),
            'ncm' => fake()->numerify('########'),
            'description' => fake()->text(),
        ];
    }
}
