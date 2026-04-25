<?php

namespace App\Domain\User;

final class Permissions
{
    public const VIEW = 'users.view';

    public const CREATE = 'users.create';

    public const UPDATE = 'users.update';

    public const DEACTIVATE = 'users.deactivate';

    public const RESTORE = 'users.restore';

    public const MANAGE_ROLES = 'users.manage_roles';

    public const MANAGE_PERMISSIONS = 'users.manage_permissions';

    public const VIEW_PROFILE = 'users.view_profile';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DEACTIVATE,
            self::RESTORE,
            self::MANAGE_ROLES,
            self::MANAGE_PERMISSIONS,
            self::VIEW_PROFILE,
        ];
    }
}
