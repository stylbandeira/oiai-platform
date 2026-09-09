<?php

namespace Tests\Feature\Integration\ListController;

use App\Jobs\AveragePriceJob;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\CompletedList;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddedProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletedListSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_list_stores_its_json_snapshot(): void
    {
        [$user, $list, $product] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk()
            ->assertJsonPath('list.status', ItensList::STATUS_COMPLETED);

        $snapshot = CompletedList::query()->where('list_id', $list->id)->firstOrFail();

        $this->assertSame('1.0', $snapshot->version);
        $this->assertSame($list->id, $snapshot->list_data['id']);
        $this->assertSame('Arroz', $snapshot->list_data['products'][0]['name']);
        $this->assertSame(2, $snapshot->list_data['products'][0]['quantity']);
        $this->assertSame(ItensList::STATUS_COMPLETED, $snapshot->list_data['status']);
    }

    public function test_show_returns_snapshot_for_completed_list(): void
    {
        [$user, $list, $product] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk();

        $product->update(['name' => 'Nome alterado depois da conclusão']);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.name', 'Arroz')
            ->assertJsonPath('list.status', ItensList::STATUS_COMPLETED);
    }

    public function test_snapshot_is_not_overwritten_after_list_is_completed(): void
    {
        [$user, $list] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['name' => 'Nome posterior'])
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.name', 'Compras');
    }

    public function test_show_keeps_using_live_data_for_active_list(): void
    {
        [$user, $list, $product] = $this->createListWithProduct();
        $product->update(['name' => 'Arroz integral']);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.name', 'Arroz integral');

        $this->assertDatabaseCount('completed_lists', 0);
    }

    public function test_updating_only_status_does_not_remove_list_items(): void
    {
        [$user, $list] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk();

        $this->assertDatabaseCount('list_products', 1);
    }

    public function test_completed_list_keeps_product_price_and_total_after_average_price_job(): void
    {
        [$user, $product, $completedList] = $this->createCompletedListAndRecalculateProductPrice();

        $this->assertSame(40.0, $product->fresh()->average_price);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $completedList->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.average_price', 10)
            ->assertJsonPath('list.total', 20);
    }

    public function test_new_list_uses_recalculated_product_price_and_total(): void
    {
        [$user, $product] = $this->createCompletedListAndRecalculateProductPrice();
        $newList = $this->createList($user, 'Lista posterior ao reajuste');
        $this->addProductToList($newList, $product, 2);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $newList->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.average_price', 40)
            ->assertJsonPath('list.total', 80);
    }

    private function createListWithProduct(): array
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create(['name' => 'Arroz']);
        $list = ItensList::create([
            'user_id' => $user->id,
            'name' => 'Compras',
            'favorite' => false,
            'total' => 12.5,
        ]);

        ListProducts::unguarded(fn () => ListProducts::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'completed' => false,
        ]));

        return [$user, $list, $product];
    }

    private function createCompletedListAndRecalculateProductPrice(): array
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create([
            'name' => 'Arroz',
            'average_price' => 10,
        ]);
        $completedList = $this->createList($user, 'Compra concluída');
        $this->addProductToList($completedList, $product, 2);

        $this->actingAs($user)
            ->putJson('/api/lists/' . $completedList->id, [
                'status' => ItensList::STATUS_COMPLETED,
            ])
            ->assertOk();

        $company = Company::factory()->create();
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 10,
        ]);

        foreach ([30, 50] as $price) {
            UserAddedProducts::unguarded(fn () => UserAddedProducts::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'product_id' => $product->id,
                'company_product_id' => $companyProduct->id,
                'price' => $price,
                'processed' => false,
                'purchase_date' => now(),
            ]));
        }

        (new AveragePriceJob)->handle();

        return [$user, $product, $completedList];
    }

    private function createList(User $user, string $name): ItensList
    {
        return ItensList::create([
            'user_id' => $user->id,
            'name' => $name,
            'favorite' => false,
            'total' => 0,
        ]);
    }

    private function addProductToList(ItensList $list, Product $product, int $quantity): void
    {
        ListProducts::unguarded(fn () => ListProducts::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'completed' => false,
        ]));
    }
}
