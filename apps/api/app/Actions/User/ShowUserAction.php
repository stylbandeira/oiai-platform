<?php

namespace App\Actions\User;

use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

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
                'events'
            ]
        );
    }
}
