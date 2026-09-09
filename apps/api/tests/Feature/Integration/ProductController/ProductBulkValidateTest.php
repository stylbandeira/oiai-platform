<?php

namespace Tests\Feature\Integration\ProductController;

use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBulkValidateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_request_is_validated(array $payload, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/products/bulk-validate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_adds_three_points_for_each_validated_product_creator(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->client()->create([
            'points' => 0,
        ]);
        $firstProduct = Product::factory()->create([
            'validated' => false,
            'created_by' => $creator->id,
            'validated_by' => null,
        ]);
        $secondProduct = Product::factory()->create([
            'validated' => false,
            'created_by' => $creator->id,
            'validated_by' => null,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/products/bulk-validate', [
                'product_ids' => [$firstProduct->id, $secondProduct->id],
                'validated' => true,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Produtos atualizados com sucesso!',
                'count' => 2,
            ]);

        $creator->refresh();

        $this->assertEquals(6, $creator->points);
        $this->assertDatabaseHas('products', [
            'id' => $firstProduct->id,
            'validated' => true,
            'validated_by' => $admin->id,
            'refined' => ProductRefinementStatus::AdminValidated->value,
            'quantity_source' => ProductQuantitySource::AdminValidated->value,
            'quantity_confidence' => ProductQuantitySource::AdminValidated->confidence(),
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $secondProduct->id,
            'validated' => true,
            'validated_by' => $admin->id,
        ]);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'product_ids'],
            [['product_ids' => 'not-an-array', 'validated' => true], 'product_ids'],
            [['product_ids' => [999999], 'validated' => true], 'product_ids.0'],
            [['product_ids' => [], 'validated' => 'not-a-boolean'], 'validated'],
        ];
    }
}
