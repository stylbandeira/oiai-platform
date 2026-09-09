<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_system_stats_returns_counts_and_health(): void
    {
        User::factory()->count(2)->create();
        Company::factory()->count(3)->create();
        Product::factory()->count(4)->create();

        $stats = app(DashboardService::class)->getSystemStats();

        $this->assertSame(2, $stats['totalUsers']);
        $this->assertSame(3, $stats['totalCompanies']);
        $this->assertSame(4, $stats['totalProducts']);
        $this->assertSame(99.5, $stats['systemHealth']);
    }

    public function test_get_top_users_orders_by_points_and_limits_results(): void
    {
        User::factory()->create(['name' => 'Low', 'points' => 1]);
        User::factory()->create(['name' => 'High', 'points' => 10]);
        User::factory()->create(['name' => 'Mid', 'points' => 5]);

        $users = app(DashboardService::class)->getTopUsers(2);

        $this->assertCount(2, $users);
        $this->assertSame('High', $users[0]->name);
        $this->assertSame('Mid', $users[1]->name);
    }

    public function test_get_top_mentioned_stores_returns_empty_array(): void
    {
        $this->assertSame([], app(DashboardService::class)->getTopMentionedStores());
    }

    public function test_get_top_mentioned_products_returns_registration_counts(): void
    {
        $user = User::factory()->client()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Produto Mencionado',
        ]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 10,
        ]);

        UserAddedProducts::unguarded(function () use ($user, $company, $product, $companyProduct) {
            UserAddedProducts::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'product_id' => $product->id,
                'company_product_id' => $companyProduct->id,
                'price' => 10,
            ]);
            UserAddedProducts::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'product_id' => $product->id,
                'company_product_id' => $companyProduct->id,
                'price' => 12,
            ]);
        });

        $products = app(DashboardService::class)->getTopMentionedProducts();

        $this->assertSame([
            [
                'id' => $product->id,
                'name' => 'Produto Mencionado',
                'registrations' => 2,
            ],
        ], $products);
    }
}
