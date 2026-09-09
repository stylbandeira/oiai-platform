<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_can_view(): void
    {
        $owner = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();
        $list = $this->createList($owner);

        $this->actingAs($admin)
            ->getJson('/api/lists/' . $list->id)
            ->assertStatus(200);

        $this->actingAs($owner)
            ->getJson('/api/lists/' . $list->id)
            ->assertStatus(200);

        $this->actingAs($otherClient)
            ->getJson('/api/lists/' . $list->id)
            ->assertStatus(200);

        $this->getJson('/api/lists/' . $list->id)
            ->assertStatus(200);
    }

    public function test_not_found_returns_error(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/lists/999999');

        $response
            ->assertStatus(404);
    }

    public function test_existing_list_returns_products(): void
    {
        $owner = User::factory()->client()->create();
        $product = Product::factory()->create(['name' => 'Macarrao']);
        $list = $this->createList($owner);
        $this->createListProduct($list, $product, 2);

        $response = $this->actingAs($owner)
            ->getJson('/api/lists/' . $list->id);

        $response
            ->assertStatus(200)
            ->assertJsonPath('list.id', $list->id)
            ->assertJsonPath('list.products.0.name', 'Macarrao')
            ->assertJsonPath('list.products.0.quantity', 2);
    }

    public function test_optimized_list_returns_companies_with_cheapest_products(): void
    {
        $owner = User::factory()->client()->create();
        $company = Company::factory()->create(['name' => 'Mercado Bom']);
        $product = Product::factory()->create(['name' => 'Cafe']);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 8.5,
        ]);
        $list = $this->createList($owner, ['optimized' => true]);
        $this->createListProduct($list, $product, 1, [
            'company_product_id' => $companyProduct->id,
        ]);

        $response = $this->actingAs($owner)
            ->getJson('/api/lists/' . $list->id);

        $response
            ->assertStatus(200)
            ->assertJsonPath('optimized', true)
            ->assertJsonFragment(['name' => 'Mercado Bom'])
            ->assertJsonFragment(['name' => 'Cafe'])
            ->assertJsonFragment(['average_price' => 8.5]);
    }

    private function createList(User $user, array $overrides = []): ItensList
    {
        return ItensList::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Lista',
            'favorite' => false,
            'total' => 0,
        ], $overrides));
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
