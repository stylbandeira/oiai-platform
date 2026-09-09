<?php

namespace Tests\Feature\Integration\FavoriteProductsController;

use App\Models\FavoriteProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteProductsFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_data_must_be_validated(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/products/' . $product->id . '/favorite', [
                'favorite' => 'not-a-boolean',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('favorite');
    }

    public function test_favorited_product_is_stored_on_database(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/products/' . $product->id . '/favorite', [
                'favorite' => true,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Produto favoritado',
            ]);

        $this->assertDatabaseHas('favorite_products', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_non_existing_product_returns_error_response(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/products/999999/favorite', [
                'favorite' => true,
            ]);

        $response->assertStatus(404);
    }

    public function test_unfavoriting_product_removes_favorite_from_database(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();

        FavoriteProducts::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/products/' . $product->id . '/favorite', [
                'favorite' => false,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Produto desfavoritado',
            ]);

        $this->assertDatabaseMissing('favorite_products', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_favorite_endpoint_keeps_toggle_behavior_when_favorite_field_is_not_sent(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/products/' . $product->id . '/favorite')
            ->assertStatus(200);

        $this->assertDatabaseHas('favorite_products', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->postJson('/api/products/' . $product->id . '/favorite')
            ->assertStatus(200);

        $this->assertDatabaseMissing('favorite_products', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }
}
