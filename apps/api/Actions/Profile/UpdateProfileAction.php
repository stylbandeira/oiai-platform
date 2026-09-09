<?php

namespace App\Actions\Profile;

use App\Models\User;

class UpdateProfileAction
{
    public function execute(User $user, array $data): void
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
