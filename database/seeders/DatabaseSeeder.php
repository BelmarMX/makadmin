<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            StateSeeder::class,
            MunicipalitySeeder::class,
            SpeciesSeeder::class,
            BreedSeeder::class,
            PelageColorSeeder::class,
            PetSizeSeeder::class,
            TemperamentSeeder::class,
            RolesSeeder::class,
            SuperAdminSeeder::class,
            DemoClinicSeeder::class,
        ]);
    }
}
