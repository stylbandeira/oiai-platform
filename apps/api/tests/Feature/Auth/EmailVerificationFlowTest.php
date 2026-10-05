<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider userTypesProvider */
    public function test_new_users_start_unverified_for_every_user_type(string $type): void
    {
        $user = User::factory()->{$type}()->unverified()->create();

        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_registration_sends_a_verification_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'cpf' => '12345678900',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'client',
        ]);

        $response->assertCreated();
        $user = User::where('email', 'new-user@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    /** @dataProvider userTypesProvider */
    public function test_unverified_users_are_blocked_and_verified_users_can_access(string $type): void
    {
        $unverified = User::factory()->{$type}()->unverified()->create();

        $this->actingAs($unverified)
            ->getJson('/api/lists')
            ->assertForbidden();

        $verified = User::factory()->{$type}()->create();

        $this->actingAs($verified)
            ->getJson('/api/lists')
            ->assertSuccessful();
    }

    /** @dataProvider userTypesProvider */
    public function test_unverified_users_cannot_login_and_verified_users_can_login(string $type): void
    {
        $unverified = User::factory()->{$type}()->unverified()->create([
            'password' => bcrypt('password'),
        ]);

        $this->postJson('/api/login', [
            'email' => $unverified->email,
            'password' => 'password',
            'user_type' => $type,
        ])->assertForbidden();

        $verified = User::factory()->{$type}()->create([
            'password' => bcrypt('password'),
        ]);

        $this->postJson('/api/login', [
            'email' => $verified->email,
            'password' => 'password',
            'user_type' => $type,
        ])->assertOk();
    }

    /** @dataProvider userTypesProvider */
    public function test_unverified_user_can_request_a_new_verification_email(string $type): void
    {
        Notification::fake();
        $user = User::factory()->{$type}()->unverified()->create();

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verification_resend_is_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->client()->unverified()->create();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->actingAs($user)
                ->postJson('/api/email/verification-notification')
                ->assertOk();
        }

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertStatus(429);
    }

    public function test_valid_signed_link_verifies_the_user(): void
    {
        $user = User::factory()->company()->unverified()->create();
        $url = URL::temporarySignedRoute('api.verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->getJson($url)->assertOk();
        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public static function userTypesProvider(): array
    {
        return [
            ['client'],
            ['company'],
            ['admin'],
        ];
    }
}
