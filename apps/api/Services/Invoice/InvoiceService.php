<?php

namespace App\Services\Invoice;

use App\Models\Invoice;

class InvoiceService
{
    public const NFE_MODEL = '55';

    public const NFCE_MODEL = '65';

    public function isValid(string $invoiceCode): bool
    {
        return $this->areaIsValid($invoiceCode);
    }

    public function areaIsValid(string $invoiceCode): bool
    {
        return in_array(substr($this->normalizeAccessKey($invoiceCode), 0, 2), array_values(Invoice::VALID_AREA_CODES), true);
    }

    public function normalizeAccessKey(string $invoiceCode): string
    {
        return preg_replace('/\D/', '', $invoiceCode) ?? '';
    }

    public function documentModel(string $invoiceCode): string
    {
        return substr($this->normalizeAccessKey($invoiceCode), 20, 2);
    }

    public function isNFCe(string $invoiceCode): bool
    {
        return $this->documentModel($invoiceCode) === self::NFCE_MODEL;
    }

}
