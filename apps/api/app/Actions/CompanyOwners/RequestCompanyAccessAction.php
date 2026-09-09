<?php

namespace App\Actions\CompanyOwners;

use App\Models\Company;
use App\Repositories\CompanyOwnerRepository;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class RequestCompanyAccessAction
{
    public function __construct(
        private NotificationService $notificationService,
        private CompanyOwnerRepository $companyOwnerRepository
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

        return response([
            'message' => 'Solicitação feita com sucesso!',
        ]);
    }
}
