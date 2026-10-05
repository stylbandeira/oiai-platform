<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailChangeVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_email_invalidates_previous_verification(): void
    {
        $user = User::factory()->client()->create([
            'email_verified_at' => now(),
        ]);

        $user->update(['email' => 'new-email@example.com']);

        $this->assertNull($user->refresh()->email_verified_at);
    }
}
