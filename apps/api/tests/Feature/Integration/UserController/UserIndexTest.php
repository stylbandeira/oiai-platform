<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidFiltersProvider
     */
    public function test_request_is_validated(array $query, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users?' . http_build_query($query));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_only_admin_users_can_access_index(): void
    {
        $client = User::factory()->client()->create();
        $company = User::factory()->company()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/users')
            ->assertStatus(403);

        $this->actingAs($company)
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_search_and_paginate_from_user_repository(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->client()->create([
            'name' => 'Alice Search',
            'email' => 'alice@example.test',
            'cpf' => '11111111111',
        ]);
        User::factory()->client()->create([
            'name' => 'Bob Filter',
            'email' => 'bob@example.test',
            'cpf' => '22222222222',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users?search=Bob&per_page=1');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bob Filter')
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_deleted_users_are_shown_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $deletedUser = User::factory()->client()->create([
            'name' => 'Deleted User',
        ]);
        $deletedUser->delete();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users?search=Deleted');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Deleted User',
            ])
            ->assertJsonPath('meta.total', 1);
    }

    public function invalidFiltersProvider(): array
    {
        return [
            [['search' => str_repeat('a', 256)], 'search'],
            [['status' => 'invalid'], 'status'],
            [['type' => 'invalid'], 'type'],
            [['sort_order' => 'sideways'], 'sort_order'],
            [['per_page' => 0], 'per_page'],
        ];
    }
}
