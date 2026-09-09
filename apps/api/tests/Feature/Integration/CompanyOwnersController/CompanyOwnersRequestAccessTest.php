<?php

namespace Tests\Feature\Integration\CompanyOwnersController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOwnersRequestAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider forbiddenUsersProvider
     */
    public function test_only_company_users_can_request_access(string $userFactoryState): void
    {
        $user = User::factory()->{$userFactoryState}()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/companies/' . $company->id . '/request-access');

        $response
            ->assertStatus(403);
    }

    /**
     * @dataProvider existingOwnershipStatusesProvider
     */
    public function test_cant_request_access_to_already_requested_or_assigned_company(string $status): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create();

        $companyUser->companies()->attach($company->id, [
            'status' => $status,
        ]);

        $response = $this->actingAs($companyUser)
            ->postJson('/api/companies/' . $company->id . '/request-access');

        $response
            ->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'Não é possível solicitar novamente atribuição à mesma empresa.',
            ]);
    }

    public function test_request_access_creates_owner_request_and_event(): void
    {
        $companyUser = User::factory()->company()->create([
            'name' => 'Usuario Empresa',
        ]);
        $company = Company::factory()->create([
            'name' => 'Empresa Solicitada',
        ]);

        $response = $this->actingAs($companyUser)
            ->postJson('/api/companies/' . $company->id . '/request-access');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Solicitação feita com sucesso!',
            ]);

        $this->assertDatabaseHas('company_owners', [
            'user_id' => $companyUser->id,
            'company_id' => $company->id,
            'status' => CompanyOwners::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('event', [
            'user_id' => $companyUser->id,
            'title' => Event::TYPE_COMPANY_OWNER_REQUEST,
            'where' => 'Empresa Solicitada',
            'target_type' => 'admin',
            'entity_type' => 'user',
            'entity_id' => $companyUser->id,
        ]);
    }

    public function forbiddenUsersProvider(): array
    {
        return [
            ['client'],
        ];
    }

    public function existingOwnershipStatusesProvider(): array
    {
        return [
            [CompanyOwners::STATUS_PENDING],
            [CompanyOwners::STATUS_ACTIVE],
        ];
    }
}
