<?php

namespace App\Actions\Auth;

use Inertia\Inertia;

class ShowConfirmablePasswordAction
{
    public function execute()
    {
        return Inertia::render('Auth/ConfirmPassword');
    }
}
