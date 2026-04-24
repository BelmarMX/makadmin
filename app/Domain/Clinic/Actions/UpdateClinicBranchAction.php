<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\DataTransferObjects\ClinicBranchData;
use App\Domain\Clinic\Models\ClinicBranch;

class UpdateClinicBranchAction
{
    public function handle(ClinicBranch $branch, ClinicBranchData $data): ClinicBranch
    {
        $branch->update([
            'name' => $data->name,
            'address' => $data->address,
            'phone' => $data->phone,
        ]);

        return $branch->fresh();
    }
}
