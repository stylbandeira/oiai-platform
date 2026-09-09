<?php

namespace App\Actions\Auth;

use Inertia\Inertia;

class CreateRegisteredUserAction
{
    public function execute()
    {
        return Inertia::render('Auth/Register');
    }
}
