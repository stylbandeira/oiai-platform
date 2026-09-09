<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider nonClientUsersProvider
     */
    public function test_only_client_users_can_use_dashboard_data(string $state): void
    {
        $user = User::factory()->{$state}()->create();

        $this->actingAs($user)
            ->getJson('/api/dashboard-data')
            ->assertStatus(403);
    }

    public function test_dashboard_data_returns_404_when_authenticated_user_does_not_exist(): void
    {
        $client = User::factory()->client()->make([
            'id' => 999999,
        ]);

        $this->actingAs($client)
            ->getJson('/api/dashboard-data')
            ->assertStatus(404);
    }

    public function nonClientUsersProvider(): array
    {
        return [
            ['admin'],
            ['company'],
        ];
    }
}
