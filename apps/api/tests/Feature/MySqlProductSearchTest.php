<?php

namespace Tests\Feature;

use App\DTO\Product\ProductSearchCriteria;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unity;
use App\Services\Product\MySqlProductSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MySqlProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_category_brand_and_dimension_filters(): void
    {
        $category = ProductCategory::factory()->create();
        $otherCategory = ProductCategory::factory()->create();
        $gram = Unity::factory()->create(['dimension' => 'mass', 'abbreviation' => 'g']);
        $liter = Unity::factory()->create(['dimension' => 'volume', 'abbreviation' => 'l']);
        $target = Product::factory()->create([
            'name' => 'Arroz integral 5kg', 'category_id' => $category->id,
            'brand_id' => 10, 'quantity_dimension' => 'mass', 'unit_id' => $gram->id,
        ]);
        Product::factory()->create([
            'name' => 'Arroz integral 5kg', 'category_id' => $otherCategory->id,
            'brand_id' => 10, 'quantity_dimension' => 'mass', 'unit_id' => $gram->id,
        ]);
        Product::factory()->create([
            'name' => 'Arroz integral 5kg', 'category_id' => $category->id,
            'brand_id' => 10, 'quantity_dimension' => 'volume', 'unit_id' => $liter->id,
        ]);

        $result = app(MySqlProductSearch::class)->search(new ProductSearchCriteria(
            query: 'arros 5kg', categoryId: $category->id, brandId: 10, dimension: 'mass',
        ));

        $this->assertSame([$target->id], $result->ids);
    }

    public function test_ean_search_is_exact_and_does_not_return_similar_codes(): void
    {
        $exact = Product::factory()->create(['ean' => '7891000100103']);
        Product::factory()->create(['ean' => '7891000100104']);

        $result = app(MySqlProductSearch::class)->search(
            new ProductSearchCriteria(query: '7891000100103'),
        );

        $this->assertSame([$exact->id], $result->ids);
        $this->assertSame($exact->id, $result->exactProduct?->id);
    }
}
