<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function deleting(User $user)
    {
        if ($user->isForceDeleting()) {
        } else {
            $user->status = 'suspended';
            $user->save();
        }
    }
}
