<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class ProcessInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_code_data' => 'required_without:invoice_code|bail|string',
            'invoice_code' => 'required_without:qr_code_data|bail|string|digits:44',
        ];
    }
}
