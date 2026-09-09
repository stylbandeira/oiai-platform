<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CompanyOwnerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_attaches_companies_with_status_and_approval_data(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->company()->create();
        $company = Company::factory()->create();
        $service = new CompanyOwnerService(Mockery::mock(NotificationService::class)->shouldIgnoreMissing());

        $service->sync($user, [
            ['id' => $company->id, 'status' => CompanyOwners::STATUS_ACTIVE],
        ], $admin->id);

        $this->assertDatabaseHas('company_owners', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => CompanyOwners::STATUS_ACTIVE,
            'approved_by' => $admin->id,
        ]);
        $this->assertNotNull($user->companies()->first()->pivot->approved_at);
    }

    public function test_sync_detaches_companies_not_sent(): void
    {
        $user = User::factory()->company()->create();
        $kept = Company::factory()->create();
        $removed = Company::factory()->create();
        $user->companies()->attach($kept->id, ['status' => CompanyOwners::STATUS_ACTIVE]);
        $user->companies()->attach($removed->id, ['status' => CompanyOwners::STATUS_ACTIVE]);
        $service = new CompanyOwnerService(Mockery::mock(NotificationService::class)->shouldIgnoreMissing());

        $service->sync($user->load('companies'), [
            ['id' => $kept->id, 'status' => CompanyOwners::STATUS_ACTIVE],
        ], null);

        $this->assertDatabaseHas('company_owners', [
            'user_id' => $user->id,
            'company_id' => $kept->id,
        ]);
        $this->assertDatabaseMissing('company_owners', [
            'user_id' => $user->id,
            'company_id' => $removed->id,
        ]);
    }

    public function test_sync_notifies_only_new_companies(): void
    {
        $user = User::factory()->company()->create();
        $oldCompany = Company::factory()->create();
        $newCompany = Company::factory()->create();
        $user->companies()->attach($oldCompany->id, ['status' => CompanyOwners::STATUS_ACTIVE]);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('userOwnershipRequestActivated')
            ->once()
            ->with(Mockery::on(fn(User $arg) => $arg->id === $user->id), Mockery::on(fn(Company $arg) => $arg->id === $newCompany->id));
        $service = new CompanyOwnerService($notificationService);

        $service->sync($user->load('companies'), [
            ['id' => $oldCompany->id, 'status' => CompanyOwners::STATUS_ACTIVE],
            ['id' => $newCompany->id, 'status' => CompanyOwners::STATUS_ACTIVE],
        ], null);
    }

    public function test_detach_removes_all_company_relationships(): void
    {
        $user = User::factory()->company()->create();
        $company = Company::factory()->create();
        $user->companies()->attach($company->id, ['status' => CompanyOwners::STATUS_ACTIVE]);
        $service = new CompanyOwnerService(Mockery::mock(NotificationService::class)->shouldIgnoreMissing());

        $service->detach($user);

        $this->assertDatabaseMissing('company_owners', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }
}
