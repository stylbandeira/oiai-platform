<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRevertDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_revert_destroy_returns_404_when_user_does_not_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/users/revertDeleted/999999')
            ->assertStatus(404);
    }

    public function test_revert_destroy_returns_error_when_user_is_not_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->client()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/users/revertDeleted/' . $target->id)
            ->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Usuário não precisa ser reativado.',
            ]);
    }

    public function test_revert_destroy_restores_soft_deleted_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->client()->create([
            'name' => 'Restored User',
        ]);
        $target->delete();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/users/revertDeleted/' . $target->id);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Usuário revertido',
                'name' => 'Restored User',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'deleted_at' => null,
        ]);
    }
}
