<?php

namespace App\Actions\Company;

use App\Models\Company;

class DestroyCompanyAction
{
    public function execute(Company $company)
    {
        $company->delete();

        return response([
            'message' => 'Empresa deletada com sucesso!',
        ]);
    }
}
