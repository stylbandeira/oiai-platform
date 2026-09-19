<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;

class ShowUserAction
{
    public function __construct(
        private UserRepository $user_repository,
    ) {}

    public function execute(int $userId)
    {
        return $this->user_repository->findWithRelations(
            $userId,
            [
                'companies',
                'pendingCompanies',
                'activeCompanies',
                'events',
            ]
        );
    }
}
