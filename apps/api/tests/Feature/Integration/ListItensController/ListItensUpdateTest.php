<?php

namespace Tests\Feature\Integration\ListItensController;

use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListItensUpdateTest extends TestCase
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
            ->putJson('/api/listItems/' . $list->id, $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_returns_404_when_list_does_not_exist(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/listItems/999999', [
                'completed_items' => [],
            ]);

        $response
            ->assertStatus(404);
    }

    public function test_completed_items_are_saved_as_completed_on_database(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $completedProduct = Product::factory()->create();
        $notCompletedProduct = Product::factory()->create();

        $this->createListProduct($list, $completedProduct);
        $this->createListProduct($list, $notCompletedProduct);

        $response = $this->actingAs($user)
            ->putJson('/api/listItems/' . $list->id, [
                'completed_items' => [$completedProduct->id],
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Itens marcados como concluídos com sucesso',
                'completed_count' => 1,
            ]);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $completedProduct->id,
            'completed' => true,
        ]);
        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $notCompletedProduct->id,
            'completed' => false,
        ]);
    }

    public function test_can_mark_only_one_item_as_completed_and_then_unmark_all_items(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $firstProduct = Product::factory()->create();
        $completedProduct = Product::factory()->create();
        $thirdProduct = Product::factory()->create();

        $this->createListProduct($list, $firstProduct);
        $this->createListProduct($list, $completedProduct);
        $this->createListProduct($list, $thirdProduct);

        $this->actingAs($user)
            ->putJson('/api/listItems/' . $list->id, [
                'completed_items' => [$completedProduct->id],
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Itens marcados como concluídos com sucesso',
                'completed_count' => 1,
            ]);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $completedProduct->id,
            'completed' => true,
        ]);

        $this->assertEquals(
            [$completedProduct->id],
            ListProducts::where('list_id', $list->id)
                ->where('completed', true)
                ->pluck('product_id')
                ->all()
        );

        $this->actingAs($user)
            ->putJson('/api/listItems/' . $list->id, [
                'completed_items' => [],
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Itens marcados como concluídos com sucesso',
                'completed_count' => 0,
            ]);

        $this->assertSame(
            0,
            ListProducts::where('list_id', $list->id)
                ->where('completed', true)
                ->count()
        );
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [['completed_items' => 'not-an-array'], 'completed_items'],
            [['completed_items' => ['not-an-integer']], 'completed_items.0'],
            [['completed_items' => [999999]], 'completed_items.0'],
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

    private function createListProduct(ItensList $list, Product $product): ListProducts
    {
        return ListProducts::unguarded(fn() => ListProducts::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'completed' => false,
        ]));
    }
}
