<?php

namespace Tests\Feature\Integration\ProductController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_request_data_is_validated(array $payload, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/products', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_client_created_product_is_saved_as_not_validated(): void
    {
        $client = User::factory()->client()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($client)
            ->postJson('/api/products', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonPath('product.validated', false);

        $this->assertDatabaseHas('products', [
            'name' => $payload['name'],
            'validated' => false,
            'created_by' => $client->id,
        ]);
    }

    public function test_company_created_product_is_attached_to_its_own_company(): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create();
        $companyUser->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_ACTIVE,
        ]);
        $payload = $this->validPayload([
            'name' => 'Produto Company',
            'sku' => 'SKU-COMPANY',
            'average_price' => 12.5,
            'company_id' => $company->id
        ]);

        $response = $this->actingAs($companyUser)
            ->postJson('/api/products', $payload);

        // dd($response);

        $response->assertStatus(200);

        $product = Product::where('sku', 'SKU-COMPANY')->first();

        $this->assertDatabaseHas('company_products', [
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 12.5,
        ]);
    }

    public function test_admin_created_product_is_not_attached_to_company(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload([
            'name' => 'Produto Admin',
            'sku' => 'SKU-ADMIN',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/products', $payload);

        $response->assertStatus(200);

        $product = Product::where('sku', 'SKU-ADMIN')->first();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Admin',
        ]);
        $this->assertDatabaseMissing('company_products', [
            'product_id' => $product->id,
        ]);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'name'],
            [['name' => 123], 'name'],
            [['name' => 'Produto'], 'sku'],
            [['name' => 'Produto', 'sku' => 'SKU', 'quantity' => 'abc'], 'quantity'],
            [['name' => 'Produto', 'sku' => 'SKU', 'quantity' => 1, 'unit_id' => 999999], 'unit_id'],
            [['name' => 'Produto', 'sku' => 'SKU', 'quantity' => 1, 'unit_id' => 1, 'category_id' => 999999], 'category_id'],
        ];
    }

    private function validPayload(array $overrides = []): array
    {
        $unity = Unity::factory()->create();
        $category = ProductCategory::factory()->create();

        return array_merge([
            'name' => 'Produto Teste',
            'sku' => 'SKU-TESTE',
            'quantity' => 1,
            'unit_id' => $unity->id,
            'category_id' => $category->id,
            'average_price' => 10.99,
            'description' => 'Produto usado em teste.',
        ], $overrides);
    }
}
