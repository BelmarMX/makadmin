<?php

namespace App\Domain\Patient\Enums;

enum PatientSize: string
{
    case Mini = 'mini';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Giant = 'giant';

    public function label(): string
    {
        return match ($this) {
            self::Mini => 'Mini',
            self::Small => 'Pequeño',
            self::Medium => 'Mediano',
            self::Large => 'Grande',
            self::Giant => 'Gigante',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()])
            ->all();
    }
}
