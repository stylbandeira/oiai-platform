<?php

namespace App\Services\CompanyOwners;

use App\Models\Company;
use App\Models\User;
use App\Services\NotificationService;

class CompanyOwnerService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function sync(User $user, array $companies, ?int $approvedBy): void
    {
        $oldCompanyIds = $user->companies
            ->pluck('id')
            ->toArray();

        $syncData = $this->normalizeForSync($user, $companies, $approvedBy);

        $user->companies()->sync($syncData);

        $newCompanyIds = array_values(array_diff(
            array_keys($syncData),
            $oldCompanyIds
        ));

        if (empty($newCompanyIds)) {
            return;
        }

        $newCompanies = Company::whereIn('id', $newCompanyIds)
            ->whereNotIn('id', $oldCompanyIds)
            ->get();

        foreach ($newCompanies as $company) {
            $this->notificationService->userOwnershipRequestActivated($user, $company);
        }
    }

    public function detach(User $user): void
    {
        $user->companies()->detach();
    }

    private function normalizeForSync(User $user, array $companies, ?int $approvedBy): array
    {
        return collect($companies)
            ->mapWithKeys(function (array $company) use ($user, $approvedBy) {
                $status = $company['status'] ?? 'active';

                return [
                    (int) $company['id'] => $this->buildPivotData(
                        $status,
                        $approvedBy
                    ),
                ];
            })
            ->all();
    }

    private function buildPivotData(string $status, ?int $approvedBy): array
    {
        return [
            'status' => $status,
            'approved_at' => $status === 'active' ? now() : null,
            'approved_by' => $status === 'active' ? $approvedBy : null,
        ];
    }
}
