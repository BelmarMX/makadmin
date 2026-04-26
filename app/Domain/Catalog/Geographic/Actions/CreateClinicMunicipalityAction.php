<?php

namespace App\Domain\Catalog\Geographic\Actions;

use App\Domain\Catalog\Geographic\Models\Municipality;

class CreateClinicMunicipalityAction
{
    public function handle(int $stateId, string $name): Municipality
    {
        return Municipality::create([
            'clinic_id' => current_clinic()->id,
            'state_id' => $stateId,
            'name' => trim($name),
            'is_active' => true,
        ]);
    }
}
