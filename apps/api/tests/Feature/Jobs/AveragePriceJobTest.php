<?php

namespace Tests\Feature\Jobs;

use App\Jobs\AveragePriceJob;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddedProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AveragePriceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_zero_is_not_included_in_product_average_price(): void
    {
        Log::spy();
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create([
            'average_price' => 0,
        ]);

        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => null,
        ]);

        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 10);
        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 20);

        (new AveragePriceJob())->handle();

        $this->assertEquals(15, $product->fresh()->average_price);
    }

    public function test_recalculates_existing_product_average_from_valid_purchases(): void
    {
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create([
            'average_price' => 30,
        ]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => null,
        ]);

        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 10);
        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 20);

        (new AveragePriceJob())->handle();

        $this->assertEquals(15, $product->fresh()->average_price);
    }

    public function test_calculates_average_from_a_single_valid_purchase(): void
    {
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create(['average_price' => 0]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => null,
        ]);

        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 27.99);

        (new AveragePriceJob())->handle();

        $this->assertEquals(27.99, $product->fresh()->average_price);
        $this->assertEquals(27.99, $companyProduct->fresh()->average_price);
    }

    public function test_uses_processed_purchases_from_the_valid_window_when_recalculating(): void
    {
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create(['average_price' => 100]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 100,
        ]);

        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 10, [
            'processed' => true,
        ]);
        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 20);

        (new AveragePriceJob())->handle();

        $this->assertEquals(15, $product->fresh()->average_price);
        $this->assertEquals(15, $companyProduct->fresh()->average_price);
    }

    public function test_updates_company_product_average_price_grouped_by_company(): void
    {
        $user = User::factory()->client()->create();
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $product = Product::factory()->create([
            'average_price' => 1,
        ]);
        $firstCompanyProduct = CompanyProducts::create([
            'company_id' => $firstCompany->id,
            'product_id' => $product->id,
            'average_price' => null,
        ]);
        $secondCompanyProduct = CompanyProducts::create([
            'company_id' => $secondCompany->id,
            'product_id' => $product->id,
            'average_price' => null,
        ]);

        $this->createUserAddedProduct($user, $firstCompany, $product, $firstCompanyProduct, 10);
        $this->createUserAddedProduct($user, $firstCompany, $product, $firstCompanyProduct, 20);
        $this->createUserAddedProduct($user, $secondCompany, $product, $secondCompanyProduct, 40);

        (new AveragePriceJob())->handle();

        $this->assertEqualsWithDelta(23.33, $product->fresh()->average_price, 0.001);

        $this->assertDatabaseHas('company_products', [
            'id' => $firstCompanyProduct->id,
            'average_price' => 15,
        ]);
        $this->assertDatabaseHas('company_products', [
            'id' => $secondCompanyProduct->id,
            'average_price' => 40,
        ]);
    }

    public function test_marks_recent_unprocessed_user_added_products_as_processed(): void
    {
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create();
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => null,
        ]);
        $first = $this->createUserAddedProduct($user, $company, $product, $companyProduct, 10);
        $second = $this->createUserAddedProduct($user, $company, $product, $companyProduct, 20);

        (new AveragePriceJob())->handle();

        $this->assertDatabaseHas('user_added_products', [
            'id' => $first->id,
            'processed' => true,
        ]);
        $this->assertDatabaseHas('user_added_products', [
            'id' => $second->id,
            'processed' => true,
        ]);
    }

    public function test_ignores_expired_and_zero_priced_purchases(): void
    {
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create([
            'average_price' => 100,
        ]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 50,
        ]);

        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 10, [
            'purchase_date' => now()->subWeeks(Product::AVERAGE_PRICE_PURCHASE_DATE_LIMIT_WEEKS + 1),
        ]);
        $this->createUserAddedProduct($user, $company, $product, $companyProduct, 0);

        (new AveragePriceJob())->handle();

        $this->assertEquals(100, $product->fresh()->average_price);
        $this->assertEquals(50, $companyProduct->fresh()->average_price);
    }

    public function test_set_average_price_returns_average_for_grouped_products(): void
    {
        $result = (new AveragePriceJob())->setAveragePrice([
            [
                ['id' => 1, 'average_price' => 10],
                ['id' => 1, 'average_price' => 20],
            ],
            [
                ['id' => 2, 'average_price' => 5],
            ],
        ]);

        $this->assertSame([
            ['id' => 1, 'average_price' => 15],
            ['id' => 2, 'average_price' => 5],
        ], $result);
    }

    private function createUserAddedProduct(
        User $user,
        Company $company,
        Product $product,
        CompanyProducts $companyProduct,
        float $price,
        array $overrides = []
    ): UserAddedProducts {
        return UserAddedProducts::unguarded(fn() => UserAddedProducts::create(array_merge([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_id' => $product->id,
            'company_product_id' => $companyProduct->id,
            'price' => $price,
            'processed' => false,
            'purchase_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides)));
    }
}
