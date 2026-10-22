<?php

namespace App\Actions\Auth;

use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\Request;

class LogoutAction
{
    public function execute(Request $request, AuthAuditLogger $audit)
    {
        $audit->record('logout', 'success', $request->user());
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
