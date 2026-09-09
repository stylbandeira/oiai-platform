<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;

class DashboardDataUserAction
{
    public function __construct(
        private UserRepository $user_repository,
    ) {}

    public function execute(int $userId)
    {
        return $this->user_repository->find($userId);
    }
}
