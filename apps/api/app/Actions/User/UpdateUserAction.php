<?php

namespace App\Actions\User;

use App\Models\User;
use App\Repositories\UserRepository;

class UpdateUserAction
{
    public function __construct(
        private UserRepository $user_repository,
    ) {}

    public function execute(array $data, int $userId)
    {
        // $currentUser = Auth::user();

        // if (!$currentUser->isAdmin() && $user->id !== $currentUser->id) {
        //     return response([
        //         'message' => 'Não autorizado',
        //     ], 403);
        // }

        unset($data['companies']);

        $user = $this->user_repository->update($userId, $data);

        return $user->load('companies');

        // $user = $this->updateUserService->execute(
        //     $user,
        //     $data,
        //     $userId
        // );
    }
}
