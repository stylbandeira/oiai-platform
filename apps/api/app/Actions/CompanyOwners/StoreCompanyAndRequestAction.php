<?php

namespace App\Actions\CompanyOwners;

use App\Http\Requests\CompanyOwners\StoreCompanyAndRequestRequest;
use App\Repositories\CompanyOwnerRepository;
use App\Repositories\CompanyRepository;
use App\Services\NotificationService;
use App\Services\UserApprovalAuditService;
use Illuminate\Support\Facades\DB;

class StoreCompanyAndRequestAction
{
    public function __construct(
        private NotificationService $notificationService,
        private CompanyRepository $companyRepository,
        private CompanyOwnerRepository $companyOwnerRepository, private UserApprovalAuditService $audit
    ) {}

    public function execute(StoreCompanyAndRequestRequest $request)
    {
        DB::transaction(function () use ($request) {
            $company = $this->companyRepository->firstOrCreateByCnpj($request->validated());

            $this->companyOwnerRepository->create($company, $request->user());

            $this->notificationService->createOwnershipRequestEvent($request->user(), $company);
            $this->audit->record($request->user(), $request->user(), 'access_requested', [
                'company_id' => $company->id,
            ]);
        });

        return response([
            'message' => 'Empresa cadastrada e solicitação enviada.',
        ]);
    }
}
