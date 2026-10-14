<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'selected_values.normalized_name' => [
                'required_without:selected_values.normalized_quantity',
                Rule::prohibitedIf(fn (): bool => $this->filled('selected_values.normalized_quantity')),
                'string',
                'max:255',
            ],
            'selected_values.normalized_quantity' => ['required_without:selected_values.normalized_name', 'string', 'max:255'],
            'selected_values.quantity_dimension' => ['required_with:selected_values.normalized_quantity', 'string', 'max:255'],
            'selected_values.search_description' => ['sometimes', 'nullable', 'string'],
            'algorithm_version' => ['sometimes', 'integer', 'min:1'],
            'validated' => ['sometimes', 'boolean'],
        ];
    }
}
