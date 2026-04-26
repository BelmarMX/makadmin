<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Models\PelageColor;

class CreateClinicPelageColorAction
{
    public function handle(string $name, ?string $hex = null): PelageColor
    {
        return PelageColor::create([
            'clinic_id' => current_clinic()->id,
            'name' => trim($name),
            'hex' => $hex,
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}
