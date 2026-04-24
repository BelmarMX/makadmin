<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\Events\ClinicActivated;
use App\Domain\Clinic\Models\Clinic;

class ActivateClinicAction
{
    public function handle(Clinic $clinic): Clinic
    {
        $clinic->update([
            'is_active' => true,
            'activated_at' => $clinic->activated_at ?? now(),
        ]);

        ClinicActivated::dispatch($clinic, auth()->user());

        return $clinic->fresh();
    }
}
