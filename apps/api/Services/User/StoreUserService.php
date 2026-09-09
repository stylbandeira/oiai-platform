<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreUserService
{
    public function __construct(
        private CompanyOwnerService $companyOwnerService
    ) {}

    public function execute(array $data, int $approvedBy): User
    {
        return DB::transaction(function () use ($data, $approvedBy) {

            $data['password'] = bcrypt(Str::uuid());
            $companies = $data['companies'] ?? [];
            unset($data['companies']);

            $user = User::create($data);

            if ($user->isCompany()) {
                $this->companyOwnerService->sync($user, $companies, $approvedBy);
            }

            return $user->load('companies');
        });
    }
}
