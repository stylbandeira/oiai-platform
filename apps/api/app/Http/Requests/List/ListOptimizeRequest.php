<?php

namespace App\Http\Requests\List;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListOptimizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => [
                'sometimes',
                'numeric',
                'between:-90,90',
                'required_with:longitude',
                'required_with:distance',
            ],
            'longitude' => [
                'sometimes',
                'numeric',
                'between:-180,180',
                'required_with:latitude',
                'required_with:distance',
            ],
            'distance' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:999.9999999',
                'required_with:latitude',
                'required_with:longitude',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response([
            'message' => 'Erro ao tentar otimizar lista',
            'errors' => $validator->errors(),
        ], 422));
    }
}
