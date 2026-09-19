<?php

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;

class ShowCompanyAction
{
    public function __construct(private CompanyRepository $companyRepo) {}

    public function execute(User $user, Company $company)
    {
        if ($user->isCompany()) {
            return $this->companyRepo->findWithRelationships($company->id, ['products']);
        }

        return $this->companyRepo->find($company->id);
    }
}
