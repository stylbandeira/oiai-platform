<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductBulkValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'validated' => 'required|boolean',
        ];
    }
}
