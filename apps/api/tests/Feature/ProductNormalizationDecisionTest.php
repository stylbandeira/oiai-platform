<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductNormalizationDecision;
use App\Models\Unity;
use App\Models\User;
use App\Services\Product\ProductNormalizationDecisionService;
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
                    'normalized_quantity' => '100 g',
                    'quantity_dimension' => 'gramas',
                ],
                'algorithm_version' => 2,
            ],
        );

        $response->assertCreated()
            ->assertJsonPath('product.name', 'BANANA')
            ->assertJsonPath('product.unity_quantity', 100)
            ->assertJsonPath('product.unity', 'g')
            ->assertJsonPath('product.unity_id', $unity->id);
        $this->assertDatabaseHas('product_normalization_decisions', [
            'product_id' => $product->id,
            'decision_source' => 'manual',
            'reviewed_by' => $user->id,
        ]);

        $product->refresh();
        $this->assertSame($unity->id, $product->unit_id);
        $this->assertSame('100 g', (string) $product->normalized_quantity);
        $this->assertSame('mass', $product->quantity_dimension);
        $this->assertSame('BANANA', $product->name);
        $this->assertSame('BANANA', $product->normalized_name);
        $this->assertNull($product->normalization_validated_at);
        $this->assertNotNull($product->quantity_normalization_validated_at);
        $this->assertNull($product->name_normalization_validated_at);
        $this->assertSame('name', $product->nextNormalizationDecisionAttribute());
        $this->assertTrue(app(ProductNormalizationDecisionService::class)->requiresDecision($product));
        $this->assertTrue(ProductNormalizationDecision::query()->where('product_id', $product->id)->exists());

        $nameResponse = $this->actingAs($user)->postJson(
            "/api/products/{$product->id}/normalization-decisions",
            [
                'selected_values' => ['normalized_name' => 'BANANA'],
                'algorithm_version' => 2,
            ],
        );

        $nameResponse->assertCreated()->assertJsonPath('product.normalization_validated', true);
        $product->refresh();
        $this->assertNotNull($product->normalization_validated_at);
        $this->assertNotNull($product->name_normalization_validated_at);
        $this->assertFalse(app(ProductNormalizationDecisionService::class)->requiresDecision($product));
    }

    public function test_repeated_correction_reaches_confidence_and_is_reused(): void
    {
        $user = User::factory()->create();
        $unity = Unity::query()->updateOrCreate(
            ['abbreviation' => 'mg'],
            [
                'name' => 'miligrama',
                'dimension' => 'mass',
                'convertion_factor' => 0.001,
            ],
        );
        $reviewedProduct = Product::factory()->create([
            'name' => 'CEFALIV 100MG 12CPR',
            'raw_name' => 'CEFALIV 100MG 12CPR',
            'normalized_name' => 'CEFALIV 100MG 12CPR',
        ]);
        $values = [
            'normalized_quantity' => '100 mg',
            'quantity_dimension' => 'mg',
        ];
        $service = app(ProductNormalizationDecisionService::class);

        $service->recordManualDecision($reviewedProduct, $values, $user->id, 2);
        $secondDecision = $service->recordManualDecision($reviewedProduct, $values, $user->id, 2);

        $this->assertSame(2, $secondDecision->confirmation_count);
        $this->assertSame(0.8, $secondDecision->confidence);

        $newProduct = Product::factory()->create([
            'name' => 'CEFALIV 100MG 12CPR',
            'raw_name' => 'CEFALIV 100MG 12CPR',
            'normalized_name' => 'CEFALIV 100MG 12CPR',
        ]);

        $this->assertTrue($service->applyConfirmedDecision($newProduct, 2));

        $newProduct->refresh();
        $this->assertSame('CEFALIV 12CPR', $newProduct->name);
        $this->assertSame(100.0, (float) $newProduct->quantity);
        $this->assertSame($unity->id, $newProduct->unit_id);
        $this->assertNull($newProduct->normalization_validated_at);
        $this->assertNotNull($newProduct->quantity_normalization_validated_at);
        $this->assertNull($newProduct->name_normalization_validated_at);
        $this->assertTrue($service->requiresDecision($newProduct));

        $service->recordManualDecision(
            $newProduct,
            ['normalized_name' => 'CEFALIV 12CPR'],
            $user->id,
            2,
        );
        $newProduct->refresh();
        $this->assertNotNull($newProduct->normalization_validated_at);
        $this->assertFalse($service->requiresDecision($newProduct));
    }

    public function test_numeric_product_requires_quantity_and_name_validation_in_sequence(): void
    {
        $user = User::factory()->create();
        $unity = Unity::factory()->create([
            'name' => 'mililitros',
            'abbreviation' => 'ml',
            'dimension' => 'volume',
        ]);
        $product = Product::factory()->create([
            'name' => 'Det. Liq. Brilux 500 ml',
            'raw_name' => 'Det. Liq. Brilux 500 ml',
            'normalized_name' => 'Det. Liq. Brilux 500 ml',
        ]);

        $quantityResponse = $this->actingAs($user)->postJson(
            "/api/products/{$product->id}/normalization-decisions",
            [
                'selected_values' => [
                    'normalized_quantity' => '500 ml',
                    'quantity_dimension' => 'ml',
                ],
                'algorithm_version' => 2,
            ],
        );

        $quantityResponse->assertCreated()
            ->assertJsonPath('product.normalization_validated', false)
            ->assertJsonPath('product.normalization_next_attribute', 'name')
            ->assertJsonPath('product.quantity_normalization_validated', true)
            ->assertJsonPath('product.name_normalization_validated', false);

        $product->refresh();
        $this->assertSame($unity->id, $product->unit_id);
        $this->assertSame('Det. Liq. Brilux', $product->name);
        $this->assertNull($product->normalization_validated_at);

        $nameResponse = $this->actingAs($user)->postJson(
            "/api/products/{$product->id}/normalization-decisions",
            [
                'selected_values' => ['normalized_name' => 'Det. Liq. Brilux'],
                'algorithm_version' => 2,
            ],
        );

        $nameResponse->assertCreated()
            ->assertJsonPath('product.normalization_validated', true)
            ->assertJsonPath('product.normalization_next_attribute', null);

        $product->refresh();
        $this->assertNotNull($product->normalization_validated_at);
        $this->assertNotNull($product->name_normalization_validated_at);
        $this->assertNotNull($product->quantity_normalization_validated_at);
        $this->assertFalse(app(ProductNormalizationDecisionService::class)->requiresDecision($product));
        $this->assertSame(2, ProductNormalizationDecision::query()->where('product_id', $product->id)->count());
    }

    public function test_validated_product_is_not_requested_for_normalization_again(): void
    {
        $product = Product::factory()->create([
            'raw_name' => 'BANANA',
            'name' => 'BANANA',
            'normalization_validated_at' => now(),
            'name_normalization_validated_at' => now(),
        ]);

        $service = app(ProductNormalizationDecisionService::class);

        $this->assertFalse($service->requiresDecision($product));
        $this->assertFalse($service->requiresDecision($product->fresh()));
    }
}
