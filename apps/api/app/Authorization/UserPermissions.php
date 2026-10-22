<?php

namespace App\Authorization;

use App\Models\User;

final class UserPermissions
{
    public const PRODUCT_CREATE = 'product.create';

    /** @var list<string> */
    private const ALL = [
        self::PRODUCT_CREATE,
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return self::ALL;
    }

    /** @return list<string> */
    public static function for(User $user): array
    {
        if ($user->isAdmin()) {
            return self::all();
        }

        return match ($user->type) {
            User::TYPE_COMPANY => [self::PRODUCT_CREATE],
            default => [],
        };
    }

    public static function allows(User $user, string $permission): bool
    {
        return in_array($permission, self::for($user), true);
    }
}
