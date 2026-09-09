<?php

namespace App\Enums;

enum ProductRefinementStatus: string
{
    case Unrefined = 'unrefined';
    case OscbrValidated = 'oscbr_validated';
    case CosmosValidated = 'cosmos_validated';
    case AdminValidated = 'admin_validated';
    case NotFound = 'not_found';

    public function canTransitionTo(self $status): bool
    {
        return $status->priority() > $this->priority();
    }

    public function allowsAutomaticRefinement(): bool
    {
        return ! in_array($this, [
            self::AdminValidated,
            self::CosmosValidated,
            self::NotFound,
        ], true);
    }

    private function priority(): int
    {
        return match ($this) {
            self::Unrefined => 0,
            self::OscbrValidated => 1,
            self::CosmosValidated => 2,
            self::AdminValidated => 3,
            self::NotFound => 0,
        };
    }
}
