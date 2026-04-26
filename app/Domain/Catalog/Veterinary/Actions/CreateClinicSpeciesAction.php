<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Models\Species;
use Illuminate\Support\Str;

class CreateClinicSpeciesAction
{
    public function handle(string $name): Species
    {
        return Species::create([
            'clinic_id' => current_clinic()->id,
            'name' => trim($name),
            'slug' => Str::slug($name),
            'sort_order' => 0,
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}
