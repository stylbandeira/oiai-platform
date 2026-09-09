<?php

namespace App\Http\Requests\Product;

use App\Rules\ExistsOr;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
            'data.*.name' => 'required|string',
            'data.*.quantity' => 'required|integer',
            'data.*.unity' => ['required', new ExistsOr('unities', ['id', 'name'])],
            'data.*.category' => ['required', new ExistsOr('product_category', ['id', 'name'])],
            'data.*.img' => 'image',
            'data.*.sku' => 'required|string|unique:products,sku',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response([
            'message' => 'Arquivo inválido',
            'errors' => $validator->errors(),
        ], 422));
    }
}
