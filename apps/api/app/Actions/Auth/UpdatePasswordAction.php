<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function execute(User $user, array $data): void
    {
        $user->update([
            'password' => Hash::make($data['password']),
        ]);
    }
}
