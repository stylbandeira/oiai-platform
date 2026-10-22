<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

class VerifyEmailAction
{
    public function execute(int $id, string $hash, AuthAuditLogger $audit)
    {
        $user = User::findOrFail($id);

        $expectedHash = sha1($user->getEmailForVerification());

        if (! hash_equals($expectedHash, $hash)) {
            Log::warning('Invalid email verification hash', ['user_id' => $user->id]);
            $audit->record('email_verification', 'failure', $user);
            abort(403, 'Hash inválido');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            $audit->record('email_verification', 'success', $user);
        }

        return response([
            'message' => 'success',
        ]);
    }
}
