<?php

namespace Tests\Feature\Integration\UserController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_request_is_validated(array $payload, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/users', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_store_returns_200_and_creates_user(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload([
            'name' => 'Created Client',
            'email' => 'created@example.test',
            'cpf' => '12345678900',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/users', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Usuário criado com sucesso!',
                'name' => 'Created Client',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Created Client',
            'email' => 'created@example.test',
            'cpf' => '12345678900',
            'type' => 'client',
        ]);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'name'],
            [['name' => 123], 'name'],
            [['name' => 'User', 'type' => 'invalid'], 'type'],
            [['name' => 'User', 'type' => 'client', 'email' => 'invalid'], 'email'],
            [['name' => 'User', 'type' => 'client', 'email' => 'user@example.test'], 'cpf'],
        ];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Client User',
            'type' => 'client',
            'email' => 'client@example.test',
            'cpf' => '12345678900',
            'status' => 'active',
        ], $overrides);
    }
}
