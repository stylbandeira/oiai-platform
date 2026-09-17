<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;

class UpdateUserService
{
    public function __construct(
        private CompanyOwnerService $companyOwnerService
    ) {}

    public function execute(User $user, array $data, int $approvedBy): User
    {
        $companies = $data['companies'] ?? [];
        unset($data['companies']);

        $user->update($data);

        if ($user->isCompany()) {
            $this->companyOwnerService->sync($user, $companies, $approvedBy);
        } else {
            $this->companyOwnerService->detach($user);
        }

        return $user->load('companies');
    }
}
