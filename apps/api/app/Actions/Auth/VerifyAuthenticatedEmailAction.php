<?php

namespace App\Actions\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyAuthenticatedEmailAction
{
    public function execute(EmailVerificationRequest $request)
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
