<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_assigned_to_company_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $companyUser = User::factory()->company()->create();
        $company = Company::factory()->create();
        $companyUser->companies()->attach($company->id, [
            'status' => CompanyOwners::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/users/' . $companyUser->id);

        $response
            ->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Apague a relação entre usuário e empresa primeiro.',
            ]);
    }

    public function test_user_cannot_delete_itself(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/users/' . $admin->id);

        $response
            ->assertStatus(403);
    }

    public function test_only_admin_users_can_delete(): void
    {
        $client = User::factory()->client()->create();
        $target = User::factory()->client()->create();

        $this->actingAs($client)
            ->deleteJson('/api/admin/users/' . $target->id)
            ->assertStatus(403);
    }

    public function test_destroy_soft_deletes_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->client()->create();

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/users/' . $target->id);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Usuário excluído com sucesso!',
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $target->id,
        ]);
    }
}
