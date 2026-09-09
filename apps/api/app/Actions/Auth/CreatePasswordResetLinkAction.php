<?php

namespace App\Actions\Auth;

use Inertia\Inertia;

class CreatePasswordResetLinkAction
{
    public function execute()
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }
}
