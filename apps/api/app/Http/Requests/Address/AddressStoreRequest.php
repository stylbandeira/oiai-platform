<?php

namespace App\Http\Requests\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddressStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country' => 'required|string',
            'area' => 'required|string',
            'city' => 'required|string',
            'street' => 'required|string',
            'number' => 'required|string',
            'complement' => 'string',
        ];
    }
}
