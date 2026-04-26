<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Models\Breed;
use Illuminate\Support\Str;

class CreateClinicBreedAction
{
    public function handle(int $speciesId, string $name): Breed
    {
        return Breed::create([
            'clinic_id' => current_clinic()->id,
            'species_id' => $speciesId,
            'name' => trim($name),
            'slug' => Str::slug($name),
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}
