<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_validation(array $payload, string $field): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/lists', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    /**
     * @dataProvider nonClientUsersProvider
     */
    public function test_only_client_can_create_lists(string $userFactoryState): void
    {
        $user = User::factory()->{$userFactoryState}()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/lists', $this->validPayload($product));

        $response->assertStatus(403);
    }

    public function test_non_existing_products_cannot_be_inserted(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/lists', [
                'name' => 'Lista',
                'products' => [
                    ['product' => ['id' => 999999], 'quantity' => 1],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('products.0.product.id');
    }

    public function test_nobody_can_create_empty_list(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/lists', [
                'name' => 'Lista',
                'products' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_success_response_returns_list_resource_with_products(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create(['name' => 'Feijao']);

        $response = $this->actingAs($user)
            ->postJson('/api/lists', $this->validPayload($product, 2));

        $response
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Lista criada com sucesso!']);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'name'],
            [['name' => 123, 'products' => []], 'name'],
            [['name' => 'Lista'], 'products'],
            [['name' => 'Lista', 'products' => []], 'products'],
            [['name' => 'Lista', 'products' => [['product' => [], 'quantity' => 1]]], 'products.0.product.id'],
            [['name' => 'Lista', 'products' => [['product' => ['id' => 1], 'quantity' => 0]]], 'products.0.quantity'],
        ];
    }

    public function nonClientUsersProvider(): array
    {
        return [
            ['admin'],
            ['company'],
        ];
    }

    private function validPayload(Product $product, int $quantity = 1): array
    {
        return [
            'name' => 'Lista de compras',
            'products' => [
                ['product' => ['id' => $product->id], 'quantity' => $quantity],
            ],
        ];
    }
}
