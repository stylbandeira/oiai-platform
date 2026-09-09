<?php

namespace App\Actions\Company;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Models\Company;
use App\Repositories\CompanyRepository;

class StoreCompanyAction
{
    public function __construct(private CompanyRepository $companyRepo) {}

    public function execute(CompanyStoreRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $company = $this->companyRepo->create($validatedData);

        return response([
            'company' => $company,
        ]);
    }
}
