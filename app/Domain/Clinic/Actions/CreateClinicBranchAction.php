<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\DataTransferObjects\ClinicBranchData;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Support\Tenancy\Scopes\ClinicScope;

class CreateClinicBranchAction
{
    public function handle(Clinic $clinic, ClinicBranchData $data): ClinicBranch
    {
        return ClinicBranch::withoutGlobalScope(ClinicScope::class)->create([
            'clinic_id' => $clinic->id,
            'name' => $data->name,
            'address' => $data->address,
            'phone' => $data->phone,
            'is_main' => false,
            'is_active' => true,
        ]);
    }
}
