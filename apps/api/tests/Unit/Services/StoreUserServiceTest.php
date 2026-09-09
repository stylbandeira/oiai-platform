<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use App\Services\User\StoreUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class StoreUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_user_with_generated_password(): void
    {
        $service = new StoreUserService(Mockery::mock(CompanyOwnerService::class)->shouldIgnoreMissing());

        $user = $service->execute([
            'name' => 'Novo Usuario',
            'type' => User::TYPE_CLIENT,
            'email' => 'novo@example.test',
            'cpf' => '12345678900',
            'status' => 'active',
        ], 1);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Novo Usuario',
            'email' => 'novo@example.test',
            'type' => User::TYPE_CLIENT,
        ]);
        $this->assertNotEmpty($user->password);
        $this->assertTrue(Hash::needsRehash($user->password) === false);
    }

    public function test_execute_syncs_companies_when_user_type_is_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $companyOwnerService = Mockery::mock(CompanyOwnerService::class);
        $companyOwnerService->shouldReceive('sync')
            ->once()
            ->with(Mockery::type(User::class), [
                ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
            ], $admin->id);

        $service = new StoreUserService($companyOwnerService);

        $service->execute([
            'name' => 'Company User',
            'type' => User::TYPE_COMPANY,
            'email' => 'company@example.test',
            'cpf' => '98765432100',
            'status' => 'active',
            'companies' => [
                ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
            ],
        ], $admin->id);
    }

    public function test_execute_does_not_sync_companies_for_non_company_user(): void
    {
        $companyOwnerService = Mockery::mock(CompanyOwnerService::class);
        $companyOwnerService->shouldNotReceive('sync');

        $service = new StoreUserService($companyOwnerService);

        $service->execute([
            'name' => 'Client User',
            'type' => User::TYPE_CLIENT,
            'email' => 'client@example.test',
            'cpf' => '11122233344',
            'status' => 'active',
        ], 1);
    }
}
