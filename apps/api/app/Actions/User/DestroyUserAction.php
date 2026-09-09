<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;

class DestroyUserAction
{
    public function __construct(
        private UserRepository $user_repository,
    ) {}

    public function execute(int $userId)
    {
        $this->user_repository->find($userId);

        return  $this->user_repository->delete($userId);
    }
}
