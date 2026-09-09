<?php

namespace App\Actions\User;

use App\Repositories\UserRepository;

class IndexUserAction
{
    public function __construct(private UserRepository $user_repository) {}

    public function execute(array $data)
    {
        return $this->user_repository->paginate(
            $data,
            [
                'with_trashed' => $data['with_trashed'],
            ]
        );
    }
}
