<?php

namespace App\Http\Requests\User;

use App\Rulesets\UserCompaniesRules;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
            'name' => 'sometimes|string',
            'type' => 'sometimes|in:client,admin,company',
            'email' => 'sometimes|email',
            'cpf' => 'sometimes|string',
            'status' => 'sometimes|in:active,inactive,suspended',
            ...UserCompaniesRules::companies()
        ];
    }
}
