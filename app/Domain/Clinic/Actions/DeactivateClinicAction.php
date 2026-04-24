<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\Events\ClinicDeactivated;
use App\Domain\Clinic\Models\Clinic;

class DeactivateClinicAction
{
    public function handle(Clinic $clinic): Clinic
    {
        $clinic->update(['is_active' => false]);

        ClinicDeactivated::dispatch($clinic, auth()->user());

        return $clinic->fresh();
    }
}
