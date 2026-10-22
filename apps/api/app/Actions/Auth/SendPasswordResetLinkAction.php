<?php

namespace App\Actions\Auth;

use App\Services\Auth\AuthAuditLogger;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class SendPasswordResetLinkAction
{
    public function execute(array $data, AuthAuditLogger $audit)
    {
        $status = Password::sendResetLink([
            'email' => $data['email'],
        ]);

        if ($status == Password::RESET_LINK_SENT) {
            $audit->record('password_recovery', 'success');

            return back()->with('status', __($status));
        }

        $audit->record('password_recovery', 'failure');

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
