<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductNormalizationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'selected_values' => ['required', 'array'],
            'selected_values.normalized_name' => ['sometimes', 'string', 'max:255'],
            'selected_values.normalized_quantity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'selected_values.quantity_dimension' => ['sometimes', 'nullable', 'string', 'max:255'],
            'selected_values.search_description' => ['sometimes', 'nullable', 'string'],
            'algorithm_version' => ['sometimes', 'integer', 'min:1'],
            'validated' => ['sometimes', 'boolean'],
        ];
    }
}
