<?php

namespace Tests\Feature\Integration\ProductController;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_returns_error(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/products/999999')
            ->assertStatus(404);
    }

    public function test_returns_product_with_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Produto Show',
            'sku' => 'SKU-SHOW',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/products/' . $product->id);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'img',
                    'ean',
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
            ->assertJsonPath('data.name', 'Produto Show');
    }
}
