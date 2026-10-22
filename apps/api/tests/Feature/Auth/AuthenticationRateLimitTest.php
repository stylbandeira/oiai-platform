<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_is_rate_limited_by_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
                ->postJson('/api/register', [
                    'email' => "rate-limit-{$attempt}@example.com",
                    'name' => 'Rate Limited User',
                    'cpf' => "1234567890{$attempt}",
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'user_type' => 'client',
                ]);

            $response->assertCreated();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/api/register', [
                'email' => 'rate-limit-6@example.com',
                'name' => 'Rate Limited User',
                'cpf' => '123456789099',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'user_type' => 'client',
            ])
            ->assertTooManyRequests();
    }

    public function test_login_is_rate_limited_by_ip_and_email(): void
    {
        $user = User::factory()->client()->create([
            'email' => 'login-rate-limit@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
                ->postJson('/api/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                    'user_type' => 'client',
                ])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'user_type' => 'client',
            ])
            ->assertTooManyRequests();
    }

    public function test_email_verification_resend_is_rate_limited(): void
    {
        $user = User::factory()->client()->create([
            'email_verified_at' => null,
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->actingAs($user)
                ->withServerVariables(['REMOTE_ADDR' => '198.51.100.12'])
                ->postJson('/api/email/verification-notification')
                ->assertOk();
        }

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.12'])
            ->postJson('/api/email/verification-notification')
            ->assertTooManyRequests();
    }
}
