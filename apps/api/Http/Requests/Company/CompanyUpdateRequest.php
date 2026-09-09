<?php

namespace App\Http\Requests\Company;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CompanyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string',
            'cnpj' => [
                'string',
                Rule::unique('company', 'cnpj')->ignore($this->route('company')?->id),
            ],
            'img' => 'image',
            'website' => 'string',
            'email' => 'string|email',
            'status' => 'string',
            'phone' => 'string',
            'description' => 'string',
            'raw_address' => 'string',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response([
            'errors' => $validator->errors(),
        ], 400));
    }
}
