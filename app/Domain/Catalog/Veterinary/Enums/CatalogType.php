<?php

namespace App\Domain\Catalog\Veterinary\Enums;

use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\PetSize;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;

enum CatalogType: string
{
    case Species = 'species';
    case Breed = 'breed';
    case PelageColor = 'pelage_color';
    case PetSize = 'pet_size';
    case Temperament = 'temperament';

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::Species => Species::class,
            self::Breed => Breed::class,
            self::PelageColor => PelageColor::class,
            self::PetSize => PetSize::class,
            self::Temperament => Temperament::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Species => 'Especies',
            self::Breed => 'Razas',
            self::PelageColor => 'Colores de pelaje',
            self::PetSize => 'Tamaños',
            self::Temperament => 'Temperamentos',
        };
    }
}
