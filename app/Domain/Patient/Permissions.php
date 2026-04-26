<?php

namespace App\Domain\Patient;

final class Permissions
{
    public const CLIENTS_VIEW = 'clients.view';

    public const CLIENTS_CREATE = 'clients.create';

    public const CLIENTS_UPDATE = 'clients.update';

    public const CLIENTS_DEACTIVATE = 'clients.deactivate';

    public const CLIENTS_RESTORE = 'clients.restore';

    public const PATIENTS_VIEW = 'patients.view';

    public const PATIENTS_CREATE = 'patients.create';

    public const PATIENTS_UPDATE = 'patients.update';

    public const PATIENTS_DEACTIVATE = 'patients.deactivate';

    public const PATIENTS_RESTORE = 'patients.restore';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CLIENTS_VIEW,
            self::CLIENTS_CREATE,
            self::CLIENTS_UPDATE,
            self::CLIENTS_DEACTIVATE,
            self::CLIENTS_RESTORE,
            self::PATIENTS_VIEW,
            self::PATIENTS_CREATE,
            self::PATIENTS_UPDATE,
            self::PATIENTS_DEACTIVATE,
            self::PATIENTS_RESTORE,
        ];
    }
}
