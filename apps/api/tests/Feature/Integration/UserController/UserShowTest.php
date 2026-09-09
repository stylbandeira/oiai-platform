<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/users/999999')
            ->assertStatus(404);
    }

    public function test_show_returns_200(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create([
            'name' => 'Visible User',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/users/' . $user->id)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Visible User');
    }

    public function test_admin_can_view_any_user(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();
        $company = User::factory()->company()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/users/' . $client->id)
            ->assertStatus(200);

        $this->actingAs($admin)
            ->getJson('/api/admin/users/' . $company->id)
            ->assertStatus(200);
    }

    /**
     * @dataProvider selfUserStatesProvider
     */
    public function test_company_and_client_can_view_only_themselves(string $state): void
    {
        $user = User::factory()->{$state}()->create();
        $otherUser = User::factory()->client()->create();

        $this->actingAs($user)
            ->getJson('/api/users/' . $user->id)
            ->assertStatus(200);

        $this->actingAs($user)
            ->getJson('/api/users/' . $otherUser->id)
            ->assertStatus(403);
    }

    public function selfUserStatesProvider(): array
    {
        return [
            ['client'],
            ['company'],
        ];
    }
}
