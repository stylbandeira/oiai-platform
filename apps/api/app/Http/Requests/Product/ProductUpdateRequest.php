<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => 'string',
            'sku' => [
                'string',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'img' => 'image',
            'unit_id' => 'exists:unities,id',
            'category_id' => 'exists:product_category,id',
            'quantity' => 'integer',
            'average_price' => 'nullable|numeric',
            'ean' => [
                'nullable',
                'string',
                Rule::unique('products', 'ean')->ignore($product?->id),
            ],
            'description' => 'nullable|string',
            'validated' => 'boolean',
        ];
    }
}
