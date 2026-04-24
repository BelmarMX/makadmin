<?php

namespace Database\Seeders;

use App\Domain\Catalog\Veterinary\Models\PelageColor;
use Illuminate\Database\Seeder;

class PelageColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Negro',     'hex' => '#1a1a1a'],
            ['name' => 'Blanco',    'hex' => '#f5f5f5'],
            ['name' => 'Café',      'hex' => '#6b3a2a'],
            ['name' => 'Dorado',    'hex' => '#d4a020'],
            ['name' => 'Gris',      'hex' => '#808080'],
            ['name' => 'Beige',     'hex' => '#d4b896'],
            ['name' => 'Atigrado',  'hex' => null],
            ['name' => 'Tricolor',  'hex' => null],
            ['name' => 'Bicolor',   'hex' => null],
            ['name' => 'Manchado',  'hex' => null],
            ['name' => 'Arena',     'hex' => '#c2a070'],
            ['name' => 'Canela',    'hex' => '#c68642'],
            ['name' => 'Calicó',    'hex' => null],
            ['name' => 'Carey',     'hex' => '#7b4f25'],
        ];

        foreach ($colors as $color) {
            PelageColor::withoutGlobalScopes()->updateOrCreate(
                ['clinic_id' => null, 'name' => $color['name']],
                array_merge($color, ['is_system' => true, 'is_active' => true]),
            );
        }
    }
}
