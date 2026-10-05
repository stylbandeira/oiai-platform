<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_verifies_an_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('api.verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->getJson($url)
            ->assertOk()
            ->assertJson(['message' => 'success']);

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_verification_requires_a_valid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson('/api/email/verify/'.$user->id.'/'.sha1($user->getEmailForVerification()))
            ->assertForbidden();

        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_verification_rejects_an_invalid_email_hash(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('api.verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1('wrong@example.com'),
        ]);

        $this->getJson($url)->assertForbidden();
        $this->assertNull($user->refresh()->email_verified_at);
    }
}
