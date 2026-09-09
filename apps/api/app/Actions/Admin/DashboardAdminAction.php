<?php

namespace App\Actions\Admin;

use App\Services\DashboardService;

class DashboardAdminAction
{
    public function execute(DashboardService $dashboardService)
    {
        return response([
            'systemStats' => $dashboardService->getSystemStats(),
            'topUsers' => $dashboardService->getTopUsers(),
            'topStores' => $dashboardService->getTopMentionedStores(),
            'topProducts' => $dashboardService->getTopMentionedProducts(),
        ]);
    }
}
