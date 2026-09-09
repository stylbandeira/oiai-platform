<?php

namespace Tests\Feature\Integration\ProductController;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_and_admin_have_access(): void
    {
        $client = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($client)
            ->getJson('/api/products')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->getJson('/api/admin/products')
            ->assertStatus(200);
    }

    public function test_client_only_sees_validated_products()
    {
        $client = User::factory()->client()->create();

        Product::factory()->create([
            'name' => 'Arroz',
            'sku' => 'SKU-ARROZ',
            'validated' => true,
        ]);

        Product::factory()->create([
            'name' => 'Arroz',
            'sku' => 'SKU-ARROZ',
            'validated' => false,
        ]);

        $this->assertDatabaseCount('products', 2);

        $this->actingAs($client)
            ->getJson('/api/products')
            ->assertJsonCount(1, 'data')
            ->assertStatus(200);
    }

    /**
     * @dataProvider invalidFiltersProvider
     */
    public function test_request_filters_are_validated(array $query, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/products?' . http_build_query($query));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_returns_products_with_resource_format(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create([
            'name' => 'Arroz',
            'sku' => 'SKU-ARROZ',
            'validated' => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/products?search=Arroz&validated=validados&per_page=5');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'sku',
                    'img',
                    'ean',
                    'average_price',
                    'validated',
                    'mentioned_quantity',
                    'mentioned_quantity_variant',
                    'unity',
                    'unity_id',
                    'unity_quantity',
                    'category',
                    'companies_count',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'quantity',
                ]],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.name', 'Arroz');
    }

    public function invalidFiltersProvider(): array
    {
        return [
            [['search' => [123]], 'search'],
            [['validated' => 'invalid'], 'validated'],
            [['per_page' => 0], 'per_page'],
            [['per_page' => 'abc'], 'per_page'],
        ];
    }
}
