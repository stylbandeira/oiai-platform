<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('validated')) {
            $this->merge([
                'validated' => filter_var(
                    $this->input('validated'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'sku' => ['required', 'string', 'unique:products,sku'],
            'quantity' => ['required', 'integer'],
            'unit_id' => ['required', 'exists:unities,id'],
            'category_id' => ['required', 'exists:product_category,id'],
            'average_price' => ['nullable', 'numeric'],
            'ean' => ['nullable', 'string', 'unique:products,ean'],
            'description' => ['nullable', 'string'],
            'validated' => ['sometimes', 'boolean'],
            'img' => ['nullable', 'image'],
            'company_id' => ['sometimes', 'exists:company,id'],
        ];
    }
}
