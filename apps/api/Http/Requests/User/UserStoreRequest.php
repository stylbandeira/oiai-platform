<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Rulesets\UserCompaniesRules;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|required',
            'type' => 'required|in:client,admin,company',
            'email' => 'email|required',
            'cpf' => 'string|required',
            'status' => 'sometimes|in:' . implode(',', User::VALID_STATUSES),
            ...UserCompaniesRules::companies()
        ];
    }
}
