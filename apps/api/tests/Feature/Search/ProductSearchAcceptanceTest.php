<?php

namespace Tests\Feature\Search;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_document_contains_the_fields_used_by_search(): void
    {
        $product = Product::factory()->create([
            'name' => 'Nescau 2.0 200g',
            'normalized_name' => 'nescau 2 0 200g',
            'search_description' => 'nescau nestle chocolate',
            'ean' => '7891000100103',
            'sku' => 'NESCAU-200G',
            'quantity_dimension' => 'mass',
        ]);

        $document = $product->toSearchableArray();

        $this->assertSame($product->id, $document['id']);
        $this->assertSame('Nescau 2.0 200g', $document['name']);
        $this->assertSame('nescau nestle chocolate', $document['description']);
        $this->assertSame('7891000100103', $document['ean']);
        $this->assertSame('NESCAU-200G', $document['sku']);
        $this->assertSame('mass', $document['quantity_dimension']);
    }

    public function test_search_configuration_supports_typo_tolerance_but_keeps_codes_exact(): void
    {
        $settings = config('scout.meilisearch.index-settings.products');

        $this->assertSame(
            ['name', 'brand', 'aliases', 'description', 'product_type', 'category', 'search_terms'],
            $settings['searchableAttributes'],
        );
        $this->assertContains('ean', $settings['filterableAttributes']);
        $this->assertContains('sku', $settings['filterableAttributes']);
        $this->assertTrue($settings['typoTolerance']['enabled']);
        $this->assertSame(['ean', 'sku'], $settings['typoTolerance']['disableOnAttributes']);
    }
}
