<?php

namespace App\Domain\Patient\Enums;

enum PatientSex: string
{
    case Male = 'male';
    case Female = 'female';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Macho',
            self::Female => 'Hembra',
            self::Unknown => 'No identificado',
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
