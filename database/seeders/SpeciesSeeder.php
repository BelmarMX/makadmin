<?php

namespace Database\Seeders;

use App\Domain\Catalog\Veterinary\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            ['id' => 1, 'name' => 'Canino',  'slug' => 'canino',  'icon' => 'dog',       'sort_order' => 10],
            ['id' => 2, 'name' => 'Felino',  'slug' => 'felino',  'icon' => 'cat',       'sort_order' => 20],
            ['id' => 3, 'name' => 'Ave',     'slug' => 'ave',     'icon' => 'bird',      'sort_order' => 30],
            ['id' => 4, 'name' => 'Roedor',  'slug' => 'roedor',  'icon' => 'rat',       'sort_order' => 40],
            ['id' => 5, 'name' => 'Reptil',  'slug' => 'reptil',  'icon' => 'snake',     'sort_order' => 50],
            ['id' => 6, 'name' => 'Conejo',  'slug' => 'conejo',  'icon' => 'rabbit',    'sort_order' => 60],
            ['id' => 7, 'name' => 'Hurón',   'slug' => 'huron',   'icon' => 'squirrel',  'sort_order' => 70],
            ['id' => 8, 'name' => 'Pez',     'slug' => 'pez',     'icon' => 'fish',      'sort_order' => 80],
            ['id' => 9, 'name' => 'Exótico', 'slug' => 'exotico', 'icon' => 'paw-print', 'sort_order' => 90],
        ];

        foreach ($species as $item) {
            Species::withoutGlobalScopes()->updateOrCreate(
                ['id' => $item['id']],
                array_merge($item, ['clinic_id' => null, 'is_system' => true, 'is_active' => true]),
            );
        }
    }
}
