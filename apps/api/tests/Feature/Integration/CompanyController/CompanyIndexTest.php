<?php

namespace Tests\Feature\Integration\CompanyController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/companies');

        $response->assertStatus(401);
    }

    public function test_admin_authorized(): void
    {
        $admin = User::factory()->make([
            'type' => 'admin'
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/companies');

        $response->assertStatus(200);
    }

    public function test_client_authorized(): void
    {
        $client = User::factory()->make([
            'type' => 'client'
        ]);

        $response = $this->actingAs($client)->getJson('/api/companies');

        $response->assertStatus(200);
    }

    public function test_company_authorized(): void
    {
        $company_user = User::factory()->make([
            'type' => 'company'
        ]);

        $response = $this->actingAs($company_user)->getJson('/api/companies');

        $response->assertStatus(200);
    }

    public function test_admin_can_see_soft_deleted_companies(): void
    {
        $admin = User::factory()->make([
            'type' => 'admin'
        ]);

        $soft_deleted_company = Company::factory()->create([
            'deleted_at' => now()
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/companies');

        $this->assertDatabaseCount('company', 1);

        $response->assertJsonCount(1, 'data')
            ->assertStatus(200);
    }

    public function test_client_cant_see_soft_deleted_companies(): void
    {
        $client = User::factory()->make([
            'type' => 'client'
        ]);

        $soft_deleted_company = Company::factory()->create([
            'deleted_at' => now()
        ]);

        $response = $this->actingAs($client)->getJson('/api/companies');

        $this->assertDatabaseCount('company', 1);

        $response->assertJsonCount(0, 'data')
            ->assertStatus(200);
    }

    public function test_search_company_returns_company()
    {
        $client = User::factory()->make([
            'type' => 'client'
        ]);

        Company::factory(20)->create();
        $company = Company::factory()->create([
            'cnpj' => '1283728392'
        ]);

        $response = $this->actingAs($client)->getJson('/api/companies?search=' . '1283728392');

        $this->assertDatabaseCount('company', 21);

        $response->assertJsonFragment([
            'name' => $company->name
        ])
            ->assertJsonCount(1, 'data')
            ->assertStatus(200);
    }

    public function test_owners_dont_return_for_client()
    {
        $client = User::factory()->make([
            'type' => 'client'
        ]);

        $company = Company::factory()->create();
        $company_owner = User::factory()->create([
            'type' => 'company'
        ]);

        $company_owner->companies()->attach($company->id);

        $response = $this->actingAs($client)->getJson('/api/companies');

        $response
            ->assertJsonFragment([
                'name' => $company->name,
            ])
            ->assertDontSee('ownership_status')
            ->assertStatus(200);
    }

    public function test_owners_return_for_owner()
    {
        $client = User::factory()->create([
            'type' => 'company'
        ]);

        $company = Company::factory()->create();

        $client->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_ACTIVE
        ]);

        $response = $this->actingAs($client)->getJson('/api/companies');

        $response
            ->assertJsonFragment([
                'name' => $company->name,
            ])
            ->assertStatus(200);
    }

    public function test_owners_dont_return_for_others_company_owners()
    {
        $client = User::factory()->create([
            'type' => 'company'
        ]);

        $company = Company::factory()->create();

        $client->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_ACTIVE
        ]);

        $not_owner_user = User::factory()->create([
            'type' => 'company'
        ]);

        $response = $this->actingAs($not_owner_user)->getJson('/api/companies');

        $response
            ->assertDontSee('ownership_status')
            ->assertStatus(200);
    }

    public function test_everyone_can_see_product_count()
    {
        $products = Product::factory()->count(10)->create();
        $company_user = User::factory()->create([
            'type' => 'company'
        ]);
        $company = Company::factory()->create();

        $company_user->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_ACTIVE
        ]);

        $company->products()->attach($products->pluck('id'));

        $response = $this->actingAs($company_user)->getJson('/api/companies');

        $response->assertJsonFragment([
            'total_products' => 10
        ])
            ->assertStatus(200);
    }
}
