<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\DB;

class RestorePatientAction
{
    public function handle(Patient $patient): Patient
    {
        return DB::transaction(function () use ($patient): Patient {
            if ($patient->trashed()) {
                $patient->restore();
            }

            $patient->update(['is_active' => true]);

            return $patient->fresh(['client', 'species', 'breed', 'temperament', 'coatColor']);
        });
    }
}
