<?php

namespace Tests\Feature\Integration\CompanyOwnersController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOwnersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_does_not_have_access(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->getJson('/api/user/company-requests');

        $response
            ->assertStatus(403);
    }

    public function test_company_user_sees_only_its_active_companies(): void
    {
        $companyUser = User::factory()->company()->create();
        $activeCompany = Company::factory()->create([
            'name' => 'Empresa Ativa',
            'cnpj' => '11222333000181',
        ]);
        $pendingCompany = Company::factory()->create([
            'name' => 'Empresa Pendente',
            'cnpj' => '12345678000190',
        ]);
        $otherCompany = Company::factory()->create([
            'name' => 'Empresa de Outro Usuario',
            'cnpj' => '99888777000166',
        ]);

        $companyUser->companies()->attach($activeCompany->id, [
            'status' => CompanyOwners::STATUS_ACTIVE,
        ]);
        $companyUser->companies()->attach($pendingCompany->id, [
            'status' => CompanyOwners::STATUS_PENDING,
        ]);

        $response = $this->actingAs($companyUser)
            ->getJson('/api/user/company-requests');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $activeCompany->id,
                'name' => 'Empresa Ativa',
            ])
            ->assertJsonMissing([
                'id' => $pendingCompany->id,
                'name' => 'Empresa Pendente',
            ])
            ->assertJsonMissing([
                'id' => $otherCompany->id,
                'name' => 'Empresa de Outro Usuario',
            ]);
    }

    public function test_admin_has_full_access(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create([
            'name' => 'Empresa Um',
            'cnpj' => '11222333000181',
        ]);
        Company::factory()->create([
            'name' => 'Empresa Dois',
            'cnpj' => '12345678000190',
        ]);
        Company::factory()->create([
            'name' => 'Empresa Tres',
            'cnpj' => '99888777000166',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/user/company-requests');

        $response
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['name' => 'Empresa Um'])
            ->assertJsonFragment(['name' => 'Empresa Dois'])
            ->assertJsonFragment(['name' => 'Empresa Tres']);
    }
}
