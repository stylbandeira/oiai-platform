<?php

namespace Tests\Feature\Integration\CompanyController;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_a_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/companies/' . $company->id);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Empresa deletada com sucesso!',
            ]);

        $this->assertSoftDeleted('company', [
            'id' => $company->id,
        ]);
    }

    public function test_client_cant_soft_delete_a_company(): void
    {
        $client = User::factory()->client()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($client)
            ->deleteJson('/api/companies/' . $company->id);

        $response
            ->assertStatus(403);

        $this->assertDatabaseHas('company', [
            'id' => $company->id,
            'deleted_at' => null
        ]);
    }

    public function test_company_cant_soft_delete_a_company(): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($companyUser)
            ->deleteJson('/api/companies/' . $company->id);

        $response
            ->assertStatus(403);

        $this->assertDatabaseHas('company', [
            'id' => $company->id,
            'deleted_at' => null
        ]);
    }
}
