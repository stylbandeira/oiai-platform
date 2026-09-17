<?php

namespace App\Actions\User;

use App\Services\User\StoreUserService;

class StoreUserAction
{
    public function __construct(private StoreUserService $storeUserService) {}

    public function execute(int $userId, array $data)
    {
        return $this->storeUserService->execute(
            $data,
            $userId
        );
    }
}
