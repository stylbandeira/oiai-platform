<?php

namespace App\Http\Requests\List;

use App\Models\ItensList;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string',
            'favorite' => 'sometimes|boolean',
            'status' => 'sometimes|in:' . implode(',', ItensList::VALID_STATUSES),
            'items' => 'sometimes|array',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response([
            'message' => 'Erro ao tentar atualizar lista',
            'errors' => $validator->errors(),
        ], 422));
    }
}
