<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthAuditLogger
{
    public function __construct(private Request $request) {}

    public function record(string $event, string $result, ?User $user = null): void
    {
        Log::channel('auth')->info('Authentication audit event', [
            'event' => $event,
            'result' => $result,
            'user_id' => $user?->getAuthIdentifier(),
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
