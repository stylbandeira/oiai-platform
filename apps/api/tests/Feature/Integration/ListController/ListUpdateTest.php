<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_validation(array $payload, string $field): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);

        $response = $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_completed_items_cannot_be_updated(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();
        $list = $this->createList($user);
        $this->createListProduct($list, $product, 1, ['completed' => true]);

        $response = $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 10],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'completed' => true,
        ]);
        $this->assertDatabaseCount('list_products', 1);
    }

    public function test_not_completed_items_can_have_quantity_changed(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();
        $list = $this->createList($user);
        $this->createListProduct($list, $product, 1, ['completed' => false]);

        $response = $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'completed' => false,
        ]);
    }

    public function test_only_list_owner_can_update(): void
    {
        $owner = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $list = $this->createList($owner);

        $response = $this->actingAs($otherUser)
            ->putJson('/api/lists/' . $list->id, [
                'name' => 'Nova lista',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('list', [
            'id' => $list->id,
            'name' => 'Lista',
        ]);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [['name' => 123], 'name'],
            [['favorite' => 'not-a-boolean'], 'favorite'],
            [['items' => 'not-an-array'], 'items'],
            [['items' => [['product_id' => 999999, 'quantity' => 1]]], 'items.0.product_id'],
            [['items' => [['product_id' => 1, 'quantity' => 0]]], 'items.0.quantity'],
        ];
    }

    private function createList(User $user): ItensList
    {
        return ItensList::create([
            'user_id' => $user->id,
            'name' => 'Lista',
            'favorite' => false,
            'total' => 0,
        ]);
    }

    private function createListProduct(ItensList $list, Product $product, int $quantity, array $overrides = []): ListProducts
    {
        return ListProducts::unguarded(fn() => ListProducts::create(array_merge([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'completed' => false,
        ], $overrides)));
    }
}
