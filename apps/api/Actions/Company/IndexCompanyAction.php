<?php

namespace App\Actions\Company;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\ClientCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;

class IndexCompanyAction
{
    public function __construct(private CompanyRepository $companyRepo) {}

    public function execute(Request $request)
    {
        $query = $this->companyRepo->list($request);

        $companies = $query->with(['owners', 'products'])
            ->withCount('products')
            ->paginate($request->per_page ?? 10);

        if ($request->user()->isAdmin()) {
            return AdminCompanyResource::collection($companies);
        }

        if ($request->user()->isCompany()) {
            return CompanyResource::collection($companies);
        }

        return ClientCompanyResource::collection($companies);
    }
}
