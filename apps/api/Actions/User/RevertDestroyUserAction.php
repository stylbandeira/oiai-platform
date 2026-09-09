<?php

namespace App\Actions\User;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Repositories\UserRepository;

class RevertDestroyUserAction
{
    public function __construct(
        private UserRepository $user_repository,
    ) {}

    public function execute(User $user)
    {
        return $this->user_repository->restore($user);
    }
}
