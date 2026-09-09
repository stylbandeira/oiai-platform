<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;

class LogoutAction
{
    public function execute(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
