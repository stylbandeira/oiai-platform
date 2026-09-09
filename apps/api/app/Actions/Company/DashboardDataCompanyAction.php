<?php

namespace App\Actions\Company;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class DashboardDataCompanyAction
{
    public function execute(Company $company)
    {
        return response([
            'company' => new CompanyResource($company),
            'totalProducts' => count($company->products) ?? 0,
            'activeWebhooks' => 0,
            'monthlyUpdates' => 0,
            'userEngagement' => 0,
        ]);
    }
}
