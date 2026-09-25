<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|string',
            'validated' => 'sometimes|in:pendentes,validados',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'category_id' => 'sometimes|integer|exists:product_category,id',
            'brand_id' => 'sometimes|integer',
            'quantity_dimension' => 'sometimes|string|max:20',
        ];
    }
}
