<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\ItensList;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_own_list_can_be_deleted(): void
    {
        $owner = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $list = $this->createList($owner);

        $this->actingAs($otherUser)
            ->deleteJson('/api/lists/' . $list->id)
            ->assertStatus(403);

        $this->actingAs($owner)
            ->deleteJson('/api/lists/' . $list->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Lista deletada com sucesso!',
            ]);

        $this->assertDatabaseMissing('list', [
            'id' => $list->id,
        ]);
    }

    public function test_not_found_returns_error(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/lists/999999');

        $response
            ->assertStatus(404);
    }

    public function nonClientUsersProvider(): array
    {
        return [
            ['admin'],
            ['company'],
        ];
    }

    private function createList(User $user): ItensList
    {
        return ItensList::create([
            'user_id' => $user->id,
            'name' => 'Lista',
            'favorite' => false,
            'total' => 0,
        ]);
    }
}
