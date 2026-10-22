<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function execute(User $user, array $data, AuthAuditLogger $audit): void
    {
        $user->update([
            'password' => Hash::make($data['password']),
        ]);
        $audit->record('password_change', 'success', $user);
    }
}
