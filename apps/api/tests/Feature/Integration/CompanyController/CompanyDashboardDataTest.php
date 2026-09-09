<?php

namespace Tests\Feature\Integration\CompanyController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_see_company_dashboard_data(): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create([
            'status' => Company::STATUS_ACTIVE,
        ]);
        $products = Product::factory()->count(2)->create();

        $companyUser->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_ACTIVE,
        ]);
        $company->products()->attach($products->pluck('id'));

        $response = $this->actingAs($companyUser)
            ->getJson('/api/companies/' . $company->id . '/dashboard');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $company->id,
                'name' => $company->name,
                'totalProducts' => 2,
                'activeWebhooks' => 0,
                'monthlyUpdates' => 0,
                'userEngagement' => 0,
            ]);
    }

    public function test_non_owner_cant_see_company_dashboard_data(): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create([
            'status' => Company::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($companyUser)
            ->getJson('/api/companies/' . $company->id . '/dashboard');

        $response
            ->assertStatus(403);
    }

    public function test_pending_owner_cant_see_company_dashboard_data(): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create([
            'status' => Company::STATUS_ACTIVE,
        ]);

        $companyUser->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_PENDING,
        ]);

        $response = $this->actingAs($companyUser)
            ->getJson('/api/companies/' . $company->id . '/dashboard');

        $response
            ->assertStatus(403);
    }
}
