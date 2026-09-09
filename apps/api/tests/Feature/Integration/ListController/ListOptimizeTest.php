<?php

namespace Tests\Feature\Integration\ListController;

use App\Jobs\AveragePriceJob;
use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddedProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ListOptimizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_returns_404(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/lists/999999/optimize');

        $response->assertStatus(404);
    }

    public function test_company_product_id_is_filled_with_cheapest_or_only_company_product(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $firstProduct = Product::factory()->create(['name' => 'Arroz']);
        $secondProduct = Product::factory()->create(['name' => 'Leite']);
        $this->createListProduct($list, $firstProduct, 1);
        $this->createListProduct($list, $secondProduct, 1);

        $expensiveCompanyProduct = $this->createCompanyProduct($firstProduct, 12.9);
        $cheapCompanyProduct = $this->createCompanyProduct($firstProduct, 9.9);
        $onlyCompanyProduct = $this->createCompanyProduct($secondProduct, 6.5);

        $response = $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize');

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['list']);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $firstProduct->id,
            'company_product_id' => $cheapCompanyProduct->id,
        ]);
        $this->assertDatabaseMissing('list_products', [
            'list_id' => $list->id,
            'product_id' => $firstProduct->id,
            'company_product_id' => $expensiveCompanyProduct->id,
        ]);
        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $secondProduct->id,
            'company_product_id' => $onlyCompanyProduct->id,
        ]);
        $this->assertDatabaseHas('list', [
            'id' => $list->id,
            'optimized' => true,
        ]);
    }

    public function test_optimized_list_is_cheaper_when_product_price_is_above_one_thousand(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $product = Product::factory()->create([
            'name' => 'Produto caro',
            'average_price' => 1250.75,
        ]);
        $this->createListProduct($list, $product, 2);

        $expensiveCompanyProduct = $this->createCompanyProduct($product, 1200.50);
        $cheapCompanyProduct = $this->createCompanyProduct($product, 1100.25);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.average_price', 1250.75);

        $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize')
            ->assertOk()
            ->assertJsonFragment(['average_price' => 1100.25]);

        $response = $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id);

        $response
            ->assertOk()
            ->assertJsonPath('list.products.0.average_price', 1250.75)
            ->assertJsonFragment(['average_price' => 1100.25]);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $cheapCompanyProduct->id,
        ]);
        $this->assertDatabaseMissing('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $expensiveCompanyProduct->id,
        ]);
    }

    public function test_zero_company_price_is_not_considered_the_cheapest(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $product = Product::factory()->create(['average_price' => 15]);
        $this->createListProduct($list, $product, 1);

        $invalidCompanyProduct = $this->createCompanyProduct($product, 0);
        $cheapCompanyProduct = $this->createCompanyProduct($product, 10);

        $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize')
            ->assertOk()
            ->assertJsonFragment(['average_price' => 10]);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $cheapCompanyProduct->id,
        ]);
        $this->assertDatabaseMissing('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $invalidCompanyProduct->id,
        ]);
    }

    public function test_company_within_maximum_distance_is_prioritized_even_when_more_expensive(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $product = Product::factory()->create(['average_price' => 25]);
        $this->createListProduct($list, $product, 1);

        $nearCompany = $this->createCompanyAt(-8.057562, -34.877001);
        $farCompany = $this->createCompanyAt(-10.047562, -34.877001);

        $nearOffer = CompanyProducts::create([
            'company_id' => $nearCompany->id,
            'product_id' => $product->id,
            'average_price' => 20,
        ]);
        $farOffer = CompanyProducts::create([
            'company_id' => $farCompany->id,
            'product_id' => $product->id,
            'average_price' => 10,
        ]);

        $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize', [
                'latitude' => -8.047562,
                'longitude' => -34.877001,
                'distance' => 100,
            ])
            ->assertOk();

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $nearOffer->id,
        ]);
        $this->assertDatabaseMissing('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $farOffer->id,
        ]);

        $companies = array_values(
            $this->actingAs($user)
                ->getJson('/api/lists/' . $list->id)
                ->assertOk()
                ->json('list.companies')
        );

        $this->assertSame($nearCompany->id, $companies[0]['company']['id']);
        $this->assertFalse($companies[0]['isTooFar']);
        $this->assertEquals(20.0, $companies[0]['products'][0]['average_price']);
    }

    public function test_calculates_and_optimizes_a_twenty_product_shopping_list(): void
    {
        Log::spy();

        $user = User::factory()->client()->create();
        $companies = Company::factory()->count(4)->create();
        $products = collect();
        $quantities = [];
        $expectedProductPrices = [];
        $expectedOptimizedPrices = [];
        $expectedCompanyIds = [];
        $expectedCompanyProductIds = [];

        for ($index = 1; $index <= 20; $index++) {
            $product = Product::factory()->create([
                'name' => "Produto {$index}",
                'average_price' => 0,
            ]);
            $cheapCompany = $companies[($index - 1) % $companies->count()];
            $expensiveCompany = $companies[$index % $companies->count()];
            $cheapPrice = 10.0 + $index;
            $expensivePrice = 20.0 + $index;
            $quantity = (($index - 1) % 3) + 1;

            $cheapCompanyProduct = CompanyProducts::create([
                'company_id' => $cheapCompany->id,
                'product_id' => $product->id,
                'average_price' => 0,
            ]);
            $expensiveCompanyProduct = CompanyProducts::create([
                'company_id' => $expensiveCompany->id,
                'product_id' => $product->id,
                'average_price' => 0,
            ]);

            foreach (
                [
                    [$cheapCompany, $cheapCompanyProduct, $cheapPrice],
                    [$expensiveCompany, $expensiveCompanyProduct, $expensivePrice],
                ] as [$company, $companyProduct, $price]
            ) {
                UserAddedProducts::unguarded(fn() => UserAddedProducts::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'company_product_id' => $companyProduct->id,
                    'price' => $price,
                    'processed' => false,
                    'purchase_date' => now(),
                ]));
            }

            $products->push($product);
            $quantities[$product->id] = $quantity;
            $expectedProductPrices[$product->id] = ($cheapPrice + $expensivePrice) / 2;
            $expectedOptimizedPrices[$product->id] = $cheapPrice;
            $expectedCompanyIds[$product->id] = $cheapCompany->id;
            $expectedCompanyProductIds[$product->id] = $cheapCompanyProduct->id;
        }

        (new AveragePriceJob())->handle();

        foreach ($products as $product) {
            $this->assertEqualsWithDelta(
                $expectedProductPrices[$product->id],
                (float) $product->fresh()->average_price,
                0.001,
            );
        }

        $list = $this->createList($user);
        foreach ($products as $product) {
            $this->createListProduct($list, $product, $quantities[$product->id]);
        }

        $regularList = $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->json('list');

        $regularTotal = collect($regularList['products'])->sum(
            fn(array $product) => $product['average_price'] * $product['quantity'],
        );
        $this->assertEqualsWithDelta(998.0, $regularTotal, 0.001);

        $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize')
            ->assertOk();

        $optimizedList = $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('optimized', true)
            ->json('list');

        $optimizedTotal = 0.0;
        $optimizedProductCount = 0;

        foreach ($optimizedList['companies'] as $companyGroup) {
            foreach ($companyGroup['products'] as $item) {
                $productId = $item['product']['id'];

                $this->assertSame($expectedCompanyIds[$productId], $companyGroup['company']['id']);
                $this->assertEqualsWithDelta(
                    $expectedOptimizedPrices[$productId],
                    (float) $item['average_price'],
                    0.001,
                );
                $this->assertDatabaseHas('list_products', [
                    'list_id' => $list->id,
                    'product_id' => $productId,
                    'company_product_id' => $expectedCompanyProductIds[$productId],
                ]);

                $optimizedTotal += $item['average_price'] * $quantities[$productId];
                $optimizedProductCount++;
            }
        }

        $this->assertSame(20, $optimizedProductCount);
        $this->assertEqualsWithDelta(803.0, $optimizedTotal, 0.001);
        $this->assertLessThan($regularTotal, $optimizedTotal);
    }

    public function test_optimize_list_with_lat_long(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $product = Product::factory()->create(['average_price' => 15]);
        $this->createListProduct($list, $product, 1);

        $cheapCompanyProduct = $this->createCompanyProduct($product, 10);

        $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize', [
                'latitude' => -9.391309,
                'longitude' => -40.524186
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get('/api/lists/' . $list->id)
            ->assertJsonFragment(["distance" => 1528.792])
            ->assertOk();

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $product->id,
            'company_product_id' => $cheapCompanyProduct->id,
        ]);
    }

    public function test_optimized_list_companies_are_ordered_from_nearest_to_farthest(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);

        $origin = [
            'latitude' => -8.047562,
            'longitude' => -34.877001,
            'distance' => 100,
        ];

        $farCompany = $this->createCompanyAt(-10.047562, -34.877001);
        $nearCompany = $this->createCompanyAt(-8.057562, -34.877001);
        $middleCompany = $this->createCompanyAt(-8.547562, -34.877001);
        $companyWithoutCoordinates = Company::factory()->create();

        foreach ([$farCompany, $companyWithoutCoordinates, $nearCompany, $middleCompany] as $company) {
            $product = Product::factory()->create(['average_price' => 15]);
            $this->createListProduct($list, $product, 1);

            CompanyProducts::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'average_price' => 10,
            ]);
        }

        $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize', $origin)
            ->assertOk();

        $companies = array_values(
            $this->actingAs($user)
                ->getJson('/api/lists/' . $list->id)
                ->assertOk()
                ->json('list.companies')
        );

        $this->assertSame(
            [
                $nearCompany->id,
                $middleCompany->id,
                $farCompany->id,
                $companyWithoutCoordinates->id,
            ],
            array_column(array_column($companies, 'company'), 'id'),
        );

        $distances = array_column($companies, 'distance');

        $this->assertCount(4, $distances);
        $this->assertLessThan($distances[1], $distances[0]);
        $this->assertLessThan($distances[2], $distances[1]);
        $this->assertNull($distances[3]);
        $this->assertSame(
            [false, false, true, true],
            array_column($companies, 'isTooFar'),
        );
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

    private function createListProduct(ItensList $list, Product $product, int $quantity): ListProducts
    {
        return ListProducts::unguarded(fn() => ListProducts::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]));
    }

    private function createCompanyProduct(Product $product, float $price): CompanyProducts
    {
        $company = Company::factory()->create();

        $address = Address::factory()->create([
            'latitude' => -22.847182,
            'longitude' => -43.47096
        ]);

        $company->address_id = $address->id;
        $company->save();

        return CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => $price,
        ]);
    }

    private function createCompanyAt(float $latitude, float $longitude): Company
    {
        $address = Address::factory()->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $company = Company::factory()->create([
            'address_id' => $address->id,
        ]);

        return $company;
    }
}
