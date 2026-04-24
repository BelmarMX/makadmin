<?php

namespace Database\Seeders;

use App\Domain\Catalog\Geographic\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::updateOrCreate(
            ['iso2' => 'MX'],
            ['id' => 1, 'iso3' => 'MEX', 'name' => 'México', 'phone_code' => '+52', 'is_active' => true],
        );
    }
}
