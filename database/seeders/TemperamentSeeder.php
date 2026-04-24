<?php

namespace Database\Seeders;

use App\Domain\Catalog\Veterinary\Models\Temperament;
use Illuminate\Database\Seeder;

class TemperamentSeeder extends Seeder
{
    public function run(): void
    {
        $temperaments = [
            'Dócil', 'Cariñoso', 'Nervioso', 'Tímido', 'Agresivo',
            'Territorial', 'Miedoso', 'Curioso', 'Protector', 'Independiente',
        ];

        foreach ($temperaments as $name) {
            Temperament::withoutGlobalScopes()->updateOrCreate(
                ['clinic_id' => null, 'name' => $name],
                ['is_system' => true, 'is_active' => true],
            );
        }
    }
}
