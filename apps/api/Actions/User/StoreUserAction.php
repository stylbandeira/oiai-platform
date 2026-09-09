<?php

namespace App\Actions\User;

use App\Http\Requests\User\UserStoreRequest;
use App\Services\User\StoreUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
