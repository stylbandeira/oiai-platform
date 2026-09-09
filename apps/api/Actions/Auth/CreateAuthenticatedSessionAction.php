<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class CreateAuthenticatedSessionAction
{
    public function execute()
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }
}
