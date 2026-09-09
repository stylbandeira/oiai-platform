<?php

namespace Tests\Feature\Integration\CompanyController;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_inactive_companies_show_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create([
            'status' => Company::STATUS_INACTIVE
        ]);

        $response = $this->actingAs($admin)->get('/api/companies/' . $company->id);

        $response
            ->assertJsonFragment([
                'id' => $company->id
            ])
            ->assertStatus(200);
    }

    /**
     * A basic feature test example.
     */
    public function test_inactive_companies_dont_show_for_client(): void
    {
        $client = User::factory()->client()->create();
        $company = Company::factory()->create([
            'status' => Company::STATUS_INACTIVE
        ]);

        $response = $this->actingAs($client)->get('/api/companies/' . $company->id);

        $response
            ->assertStatus(404);
    }

    public function test_others_companies_dont_show_for_company(): void
    {
        $user_company = Company::factory()->create([
            'status' => Company::STATUS_ACTIVE
        ]);

        $company_user = User::factory()->company()->create();
        $company_user->companies()->attach($user_company);

        $company = Company::factory()->create([
            'status' => Company::STATUS_INACTIVE
        ]);

        $response = $this->actingAs($company_user)->get('/api/companies/' . $company->id);

        $response
            ->assertStatus(403);
    }

    public function test_inactive_company_dont_show_for_itself(): void
    {
        $user_company = Company::factory()->create([
            'status' => Company::STATUS_INACTIVE
        ]);

        $company_user = User::factory()->company()->create();
        $company_user->companies()->attach($user_company);

        $response = $this->actingAs($company_user)->get('/api/companies/' . $user_company->id);

        $response
            ->assertStatus(403);
    }
}
