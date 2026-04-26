<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Models\Temperament;

class CreateClinicTemperamentAction
{
    public function handle(string $name): Temperament
    {
        return Temperament::create([
            'clinic_id' => current_clinic()->id,
            'name' => trim($name),
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}
