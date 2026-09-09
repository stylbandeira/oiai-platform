<?php

namespace App\Enums;

enum ProductQuantitySource: string
{
    case DefaultExtraction = 'default_extraction';
    case CosmosExtraction = 'cosmos_extraction';
    case AdminValidated = 'admin_validated';

    public function confidence(): float
    {
        return match ($this) {
            self::DefaultExtraction => 0.90,
            self::CosmosExtraction => 0.95,
            self::AdminValidated => 1.00,
        };
    }
}
