<?php

namespace App\Domain\Patient\Actions;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Patient\DataTransferObjects\PatientData;
use App\Domain\Patient\Events\PatientUpdated;
use App\Domain\Patient\Models\Patient;
use Illuminate\Support\Facades\DB;

class UpdatePatientAction
{
    public function __construct(
        private readonly MediaStorage $mediaStorage,
    ) {}

    public function handle(Patient $patient, PatientData $data, UploadPatientPhotoAction $uploader): Patient
    {
        return DB::transaction(function () use ($patient, $data, $uploader): Patient {
            $attributes = $data->toArray();

            if ($data->photo) {
                if ($patient->photo_path) {
                    $this->mediaStorage->delete($patient->photo_path);
                }

                $attributes['photo_path'] = $uploader->handle($data->photo, $patient->clinic_id);
            }

            $patient->update($attributes);

            PatientUpdated::dispatch($patient, auth()->user());

            return $patient->fresh(['client', 'species', 'breed', 'temperament', 'coatColor']);
        });
    }
}
