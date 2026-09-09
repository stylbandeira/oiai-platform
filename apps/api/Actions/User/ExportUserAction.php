<?php

namespace App\Actions\User;

use App\Exports\Mappers\UserExportMapper;
use App\Http\Requests\User\IndexUserRequest;
use App\Repositories\UserRepository;
use App\Services\ExportService;

class ExportUserAction
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function execute(IndexUserRequest $request, ExportService $exportService, UserExportMapper $mapper)
    {
        $users = $this->userRepository->list($request->validated());

        return $exportService->exportToCSV(
            $users,
            $mapper->columns(),
            'usuarios'
        );
    }
}
