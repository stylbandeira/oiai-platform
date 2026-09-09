<?php

namespace App\Rulesets;

use App\Models\Company;
use App\Models\CompanyOwners;
use Illuminate\Validation\Rule;

class UserCompaniesRules
{
    public static function companies(): array
    {
        return [
            'companies' => ['nullable', 'array'],
            'companies.*.id' => [
                'integer',
                'distinct',
                Rule::exists('company', 'id')->where('status', 'active'),
            ],
            'companies.*.status' => ['required', 'in:' . implode(',', CompanyOwners::VALID_STATUSES)],
        ];
    }
}
