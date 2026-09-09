<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use App\Services\User\UpdateUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UpdateUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_updates_user_data(): void
    {
        $user = User::factory()->client()->create([
            'name' => 'Old Name',
        ]);
        $companyOwnerService = Mockery::mock(CompanyOwnerService::class);
        $companyOwnerService->shouldReceive('detach')->once()->with(Mockery::on(fn(User $arg) => $arg->id === $user->id));
        $service = new UpdateUserService($companyOwnerService);

        $updated = $service->execute($user, [
            'name' => 'New Name',
            'email' => 'new@example.test',
        ], 1);

        $this->assertSame('New Name', $updated->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.test',
        ]);
    }

    public function test_execute_syncs_companies_for_company_user(): void
    {
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create();
        $companyOwnerService = Mockery::mock(CompanyOwnerService::class);
        $companyOwnerService->shouldReceive('sync')
            ->once()
            ->with(Mockery::on(fn(User $arg) => $arg->id === $companyUser->id), [
                ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
            ], 1);
        $service = new UpdateUserService($companyOwnerService);

        $service->execute($companyUser, [
            'companies' => [
                ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
            ],
        ], 1);
    }

    public function test_execute_detaches_companies_for_non_company_user(): void
    {
        $user = User::factory()->client()->create();
        $companyOwnerService = Mockery::mock(CompanyOwnerService::class);
        $companyOwnerService->shouldReceive('detach')
            ->once()
            ->with(Mockery::on(fn(User $arg) => $arg->id === $user->id));
        $service = new UpdateUserService($companyOwnerService);

        $service->execute($user, [
            'name' => 'Updated Client',
        ], 1);
    }
}
