<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductNormalizationDecision;
use App\Models\Unity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductNormalizationDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantity_and_unity_decision_is_applied_and_recorded(): void
    {
        $user = User::factory()->create();
        $unity = Unity::factory()->create([
            'name' => 'gramas',
            'abbreviation' => 'g',
            'dimension' => 'mass',
        ]);
        $product = Product::factory()->create([
            'name' => 'BANANA 100G',
            'raw_name' => 'BANANA 100G',
            'normalized_name' => 'banana 100g',
        ]);

        $response = $this->actingAs($user)->postJson(
            "/api/products/{$product->id}/normalization-decisions",
            [
                'selected_values' => [
                    'normalized_quantity' => '100',
                    'quantity_dimension' => 'gramas',
                ],
                'algorithm_version' => 2,
            ],
        );

        $response->assertCreated();
        $this->assertDatabaseHas('product_normalization_decisions', [
            'product_id' => $product->id,
            'decision_source' => 'manual',
            'reviewed_by' => $user->id,
        ]);

        $product->refresh();
        $this->assertSame($unity->id, $product->unit_id);
        $this->assertSame('100', (string) $product->normalized_quantity);
        $this->assertSame('mass', $product->quantity_dimension);
        $this->assertSame('banana', $product->normalized_name);
        $this->assertTrue(ProductNormalizationDecision::query()->where('product_id', $product->id)->exists());
    }
}
