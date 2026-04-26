<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Events\PatientUpdated;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\DB;

class LinkPatientToClientAction
{
    public function handle(Patient $patient, Client $newClient): Patient
    {
        abort_unless($patient->clinic_id === $newClient->clinic_id, 403);

        return DB::transaction(function () use ($patient, $newClient): Patient {
            $patient->update(['client_id' => $newClient->id]);

            PatientUpdated::dispatch($patient, auth()->user());

            return $patient->fresh();
        });
    }
}
