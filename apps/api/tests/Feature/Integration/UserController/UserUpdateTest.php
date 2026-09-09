<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/users/999999', [
                'name' => 'Missing',
            ])
            ->assertStatus(404);
    }

    public function test_update_returns_200(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create([
            'name' => 'Old Name',
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/users/' . $user->id, [
                'name' => 'New Name',
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Usuário editado com sucesso!',
                'name' => 'New Name',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_company_user_update_assigns_sent_companies(): void
    {
        $admin = User::factory()->admin()->create();
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create([
            'status' => Company::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/users/' . $companyUser->id, [
                'companies' => [
                    ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('company_owners', [
            'user_id' => $companyUser->id,
            'company_id' => $company->id,
            'status' => CompanyOwners::STATUS_ACTIVE,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_company_user_update_detaches_existing_companies_not_sent(): void
    {
        $admin = User::factory()->admin()->create();
        $companyUser = User::factory()->company()->create();
        $keptCompany = Company::factory()->create(['status' => Company::STATUS_ACTIVE]);
        $removedCompany = Company::factory()->create(['status' => Company::STATUS_ACTIVE]);

        $companyUser->companies()->attach($keptCompany->id, [
            'status' => CompanyOwners::STATUS_ACTIVE,
        ]);
        $companyUser->companies()->attach($removedCompany->id, [
            'status' => CompanyOwners::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/users/' . $companyUser->id, [
                'companies' => [
                    ['id' => $keptCompany->id, 'status' => CompanyOwners::STATUS_ACTIVE],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('company_owners', [
            'user_id' => $companyUser->id,
            'company_id' => $keptCompany->id,
        ]);
        $this->assertDatabaseMissing('company_owners', [
            'user_id' => $companyUser->id,
            'company_id' => $removedCompany->id,
        ]);
    }

    public function test_company_assignment_creates_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create([
            'name' => 'Empresa Notificada',
            'status' => Company::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/users/' . $companyUser->id, [
                'companies' => [
                    ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('event', [
            'user_id' => $companyUser->id,
            'target_type' => 'company',
            'title' => 'company_ownership_active',
            'entity_type' => 'user',
            'entity_id' => $companyUser->id,
        ]);
    }
}
