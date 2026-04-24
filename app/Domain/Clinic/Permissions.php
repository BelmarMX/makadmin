<?php

namespace App\Domain\Clinic;

final class Permissions
{
    public const VIEW = 'clinics.view';

    public const CREATE = 'clinics.create';

    public const UPDATE = 'clinics.update';

    public const DELETE = 'clinics.delete';

    public const MANAGE_MODULES = 'clinics.manage_modules';

    public const MANAGE_BRANCHES = 'clinics.manage_branches';

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::MANAGE_MODULES,
            self::MANAGE_BRANCHES,
        ];
    }
}
