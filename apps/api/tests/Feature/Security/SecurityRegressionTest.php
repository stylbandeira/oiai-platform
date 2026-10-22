<?php

namespace Tests\Feature\Security;

use App\Models\ItensList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/lists')->assertUnauthorized();
    }

    public function test_client_cannot_read_another_clients_list(): void
    {
        $owner = User::factory()->client()->create();
        $other = User::factory()->client()->create();
        $list = ItensList::create([
            'user_id' => $owner->id,
            'name' => 'Privada',
            'favorite' => false,
            'total' => 0,
        ]);

        $this->actingAs($other)
            ->getJson('/api/lists/'.$list->id)
            ->assertForbidden();
    }

    public function test_registration_cannot_escalate_user_type(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Attacker',
            'cpf' => '12345678900',
            'email' => 'attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'admin',
        ])->assertUnprocessable();
    }

    public function test_unknown_cors_origin_is_not_allowed(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://attacker.example',
            'Access-Control-Request-Method' => 'GET',
        ])->call('OPTIONS', '/api/lists');

        $this->assertNotSame('https://attacker.example', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
