<?php

namespace App\Actions\Company;

use App\Http\Resources\CompanyResource;
use App\Models\Company;

class DashboardDataCompanyAction
{
    public function execute(Company $company)
    {
        return response([
            'company' => new CompanyResource($company),
            'totalProducts' => $company->products->count(),
            'activeWebhooks' => 0,
            'monthlyUpdates' => 0,
            'userEngagement' => 0,
        ]);
    }
}
