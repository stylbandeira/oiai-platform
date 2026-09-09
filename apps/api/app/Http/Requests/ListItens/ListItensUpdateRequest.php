<?php

namespace App\Http\Requests\ListItens;

use Illuminate\Foundation\Http\FormRequest;

class ListItensUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completed_items' => 'sometimes|array',
            'completed_items.*' => 'integer|exists:products,id',
        ];
    }
}
