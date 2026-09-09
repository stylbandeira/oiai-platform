<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Policies\UsersPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();

        $target = User::factory()->client()->create();

        $policy = new UsersPolicy();

        $this->assertTrue($policy->delete($admin, $target));
    }

    public function test_client_can_see_dashboard_data(): void
    {
        $client = User::factory()->client()->create();

        $policy = new UsersPolicy();

        $this->assertTrue($policy->dashboardData($client));
    }

    public function test_client_cannot_delete_user(): void
    {
        $client = User::factory()->client()->create();

        $target = User::factory()->client()->create();

        $policy = new UsersPolicy();

        $this->assertFalse($policy->delete($client, $target));
    }

    public function test_admin_can_delete_user_using_gate(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->client()->create();

        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('delete', $target));
    }

    public function test_client_cannot_delete_user_by_route(): void
    {
        $client = User::factory()->client()->create();
        $target = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->deleteJson("/api/admin/users/{$target->id}");

        $response->assertStatus(403);
    }
}
