<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Events\PatientDeactivated;
use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\DB;

class DeactivatePatientAction
{
    public function handle(Patient $patient): void
    {
        DB::transaction(function () use ($patient): void {
            $patient->update(['is_active' => false]);

            if (! $patient->trashed()) {
                $patient->delete();
            }

            PatientDeactivated::dispatch($patient, auth()->user());
        });
    }
}
