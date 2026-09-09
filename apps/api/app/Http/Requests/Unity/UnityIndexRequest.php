<?php

namespace App\Http\Requests\Unity;

use Illuminate\Foundation\Http\FormRequest;

class UnityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
