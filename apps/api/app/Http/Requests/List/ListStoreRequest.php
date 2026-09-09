<?php

namespace App\Http\Requests\List;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'products' => 'required|array|min:1',
            'products.*.product.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response([
            'message' => 'Erro ao tentar criar lista',
            'errors' => $validator->errors(),
        ], 422));
    }
}
