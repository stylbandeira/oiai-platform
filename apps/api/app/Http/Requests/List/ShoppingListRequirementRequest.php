<?php

namespace App\Http\Requests\List;

use Illuminate\Foundation\Http\FormRequest;

final class ShoppingListRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'desired_quantity' => ['required', 'numeric', 'gt:0'],
            'desired_unit_id' => ['required', 'integer', 'exists:unities,id'],
            'brand_id' => ['nullable', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'specific_product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
