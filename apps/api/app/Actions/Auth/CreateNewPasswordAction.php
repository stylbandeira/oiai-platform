<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CreateNewPasswordAction
{
    public function execute(Request $request)
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }
}
