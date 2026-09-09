<?php

namespace Tests\Feature\Integration\ProductController;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_request_data_is_validated(array $payload, string $field): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/products/' . $product->id, $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_client_cannot_update_already_validated_product(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create([
            'validated' => true,
        ]);

        $response = $this->actingAs($client)
            ->putJson('/api/products/' . $product->id, [
                'name' => 'Tentativa',
            ]);

        $response
            ->assertStatus(403);
    }

    public function test_changed_data_reflects_on_database_after_refresh(): void
    {
        $admin = User::factory()->admin()->create();
        $unity = Unity::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Produto Antigo',
            'sku' => 'SKU-OLD',
        ]);

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/products/' . $product->id, [
                'name' => 'Produto Atualizado',
                'sku' => 'SKU-NEW',
                'quantity' => 7,
                'unit_id' => $unity->id,
                'category_id' => $category->id,
                'average_price' => 22.5,
                'description' => 'Descricao atualizada.',
            ]);

        $response->assertStatus(200);

        $product->refresh();

        $this->assertSame('Produto Atualizado', $product->name);
        $this->assertSame('SKU-NEW', $product->sku);
        $this->assertEquals(7, $product->quantity);
        $this->assertEquals(22.5, $product->average_price);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Atualizado',
            'sku' => 'SKU-NEW',
            'description' => 'Descricao atualizada.',
        ]);
    }

    public function test_update_returns_product_with_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Produto Antigo',
        ]);

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/products/' . $product->id, [
                'name' => 'Produto Resource',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'product' => [
                    'id',
                    'name',
                    'sku',
                    'average_price',
                    'validated',
                    'unity',
                    'unity_id',
                    'category',
                    'created_at',
                    'updated_at',
                    'quantity',
                ],
            ])
            ->assertJsonPath('product.name', 'Produto Resource');
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [['name' => 123], 'name'],
            [['sku' => 123], 'sku'],
            [['quantity' => 'abc'], 'quantity'],
            [['unit_id' => 999999], 'unit_id'],
            [['category_id' => 999999], 'category_id'],
            [['average_price' => 'abc'], 'average_price'],
            [['validated' => 'not-a-boolean'], 'validated'],
        ];
    }
}
