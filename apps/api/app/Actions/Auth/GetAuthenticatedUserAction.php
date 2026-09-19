<?php

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;

class GetAuthenticatedUserAction
{
    public function execute(User $user)
    {
        if ($user->isCompany()) {
            $user->load(['companies', 'activeCompanies', 'pendingCompanies', 'events']);

            return response([
                'user' => (new UserResource($user))->withNotifications(),
            ]);
        }

        if ($user->isAdmin()) {
            return response([
                'user' => (new UserResource($user))->withNotifications(),
            ]);
        }

        return response([
            'user' => (new UserResource($user))->withNotifications(),
        ]);
    }
}
