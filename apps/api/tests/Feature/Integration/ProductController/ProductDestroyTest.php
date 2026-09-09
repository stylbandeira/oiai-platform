<?php

namespace Tests\Feature\Integration\ProductController;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson('/api/admin/products/999999')
            ->assertStatus(404);
    }

    public function test_product_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->assertDatabaseCount('products', 1);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/products/' . $product->id);

        $this->assertDatabaseCount('products', 1);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Produto deletada com sucesso!',
            ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }
}
