<?php

namespace App\Actions\CompanyOwners;

use App\Models\Company;
use App\Repositories\CompanyOwnerRepository;
use App\Services\NotificationService;
use App\Services\UserApprovalAuditService;
use Illuminate\Http\Request;

class RequestCompanyAccessAction
{
    public function __construct(
        private NotificationService $notificationService,
        private CompanyOwnerRepository $companyOwnerRepository,
        private UserApprovalAuditService $audit
    ) {}

    public function execute(Request $request, Company $company)
    {
        try {
            $this->companyOwnerRepository->create($company, $request->user());
        } catch (\Throwable $th) {
            return response([
                'error' => 'Não é possível solicitar novamente atribuição à mesma empresa.',
            ], 400);
        }

        $this->notificationService->createOwnershipRequestEvent($request->user(), $company);
        $this->audit->record($request->user(), $request->user(), 'access_requested', [
            'company_id' => $company->id,
        ]);

        return response([
            'message' => 'Solicitação feita com sucesso!',
        ]);
    }
}
