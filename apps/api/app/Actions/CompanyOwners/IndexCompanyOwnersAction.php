<?php

namespace App\Actions\CompanyOwners;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;

class IndexCompanyOwnersAction
{
    public function __construct(private CompanyRepository $companyRepo) {}

    public function execute(Request $request)
    {
        $user = $request->user();

        $query = $this->companyRepo->list($request);

        $companies = $this->companyRepo->paginateForUser($user, 10);

        if ($user->isAdmin()) {
            return AdminCompanyResource::collection($companies);
        }

        if ($request->user()->isCompany()) {
            return CompanyResource::collection($companies);
        }
    }
}
