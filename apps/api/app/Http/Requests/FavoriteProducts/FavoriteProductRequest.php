<?php

namespace App\Http\Requests\FavoriteProducts;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'favorite' => 'sometimes|boolean',
        ];
    }
}
