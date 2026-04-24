<?php

namespace Database\Seeders;

use App\Domain\Catalog\Veterinary\Models\PetSize;
use Illuminate\Database\Seeder;

class PetSizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['name' => 'Toy',     'weight_min_kg' => 0,     'weight_max_kg' => 4,    'sort_order' => 10],
            ['name' => 'Pequeño', 'weight_min_kg' => 4.01,  'weight_max_kg' => 10,   'sort_order' => 20],
            ['name' => 'Mediano', 'weight_min_kg' => 10.01, 'weight_max_kg' => 25,   'sort_order' => 30],
            ['name' => 'Grande',  'weight_min_kg' => 25.01, 'weight_max_kg' => 45,   'sort_order' => 40],
            ['name' => 'Gigante', 'weight_min_kg' => 45.01, 'weight_max_kg' => null, 'sort_order' => 50],
        ];

        foreach ($sizes as $size) {
            PetSize::withoutGlobalScopes()->updateOrCreate(
                ['clinic_id' => null, 'name' => $size['name']],
                array_merge($size, ['is_system' => true, 'is_active' => true]),
            );
        }
    }
}
