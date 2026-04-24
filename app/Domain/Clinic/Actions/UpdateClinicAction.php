<?php

namespace App\Domain\Clinic\Actions;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\DataTransferObjects\ClinicData;
use App\Domain\Clinic\Models\Clinic;
use Illuminate\Support\Facades\DB;

class UpdateClinicAction
{
    public function __construct(private readonly MediaStorage $media) {}

    public function handle(Clinic $clinic, ClinicData $data): Clinic
    {
        return DB::transaction(function () use ($clinic, $data): Clinic {
            $logoPath = $clinic->logo_path;

            if ($data->logo) {
                if ($logoPath) {
                    $this->media->delete($logoPath);
                }
                $logoPath = $this->media->put("clinics/{$clinic->slug}/logo", $data->logo);
            }

            $clinic->update([
                'legal_name' => $data->legalName,
                'commercial_name' => $data->commercialName,
                'rfc' => $data->rfc,
                'fiscal_regime' => $data->fiscalRegime?->value,
                'tax_address' => $data->taxAddress,
                'responsible_vet_name' => $data->responsibleVetName,
                'responsible_vet_license' => $data->responsibleVetLicense,
                'contact_phone' => $data->contactPhone,
                'contact_email' => $data->contactEmail,
                'primary_color' => $data->primaryColor,
                'logo_path' => $logoPath,
            ]);

            return $clinic->fresh();
        });
    }
}
