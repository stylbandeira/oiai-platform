<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

class VerifyEmailAction
{
    public function execute(int $id, string $hash)
    {
        $user = User::findOrFail($id);

        $expectedHash = sha1($user->getEmailForVerification());

        if (!hash_equals($expectedHash, $hash)) {
            Log::error("Hash inválido. Esperado: $expectedHash, recebido: $hash");
            abort(403, 'Hash inválido');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response([
            'message' => 'success',
        ]);
    }
}
