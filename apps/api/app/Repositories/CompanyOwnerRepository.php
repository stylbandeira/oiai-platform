<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;

class CompanyOwnerRepository
{
    protected CompanyOwners $companyOwner;

    public function __construct(CompanyOwners $companyOwner)
    {
        $this->companyOwner = $companyOwner;
    }

    public function all()
    {
        return $this->companyOwner->all();
    }

    public function find($id)
    {
        return $this->companyOwner->findOrFail($id);
    }

    public function create(Company $company, User $user, string $status = CompanyOwners::STATUS_PENDING)
    {
        return $this->companyOwner->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => $status,
        ]);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->companyOwner->destroy($id);
    }
}
