<?php

namespace App\Actions\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class SendEmailVerificationNotificationAction
{
    public function execute(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
