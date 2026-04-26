<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\DataTransferObjects\PatientData;
use App\Domain\Patient\Events\PatientCreated;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\DB;

class CreatePatientAction
{
    public function handle(PatientData $data, Client $client, UploadPatientPhotoAction $uploader): Patient
    {
        return DB::transaction(function () use ($data, $client, $uploader): Patient {
            $photoPath = $data->photo ? $uploader->handle($data->photo, $client->clinic_id) : null;

            $patient = Patient::create([
                ...$data->toArray(),
                'clinic_id' => $client->clinic_id,
                'client_id' => $client->id,
                'photo_path' => $photoPath,
                'is_active' => true,
            ]);

            PatientCreated::dispatch($patient, auth()->user());

            return $patient;
        });
    }
}
