<?php

namespace App\Domain\User\Enums;

enum UserRole: string
{
    case ClinicAdmin = 'clinic_admin';
    case Veterinarian = 'veterinarian';
    case Groomer = 'groomer';
    case Receptionist = 'receptionist';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::ClinicAdmin => 'Administrador de clínica',
            self::Veterinarian => 'Veterinario',
            self::Groomer => 'Esteticista',
            self::Receptionist => 'Recepcionista',
            self::Cashier => 'Cajero',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
