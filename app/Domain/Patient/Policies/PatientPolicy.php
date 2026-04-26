<?php

namespace App\Domain\Patient\Policies;

use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Permissions;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PATIENTS_VIEW);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient->clinic_id) && $user->can(Permissions::PATIENTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PATIENTS_CREATE);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient->clinic_id) && $user->can(Permissions::PATIENTS_UPDATE);
    }

    public function deactivate(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient->clinic_id) && $user->can(Permissions::PATIENTS_DEACTIVATE);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient->clinic_id) && $user->can(Permissions::PATIENTS_RESTORE);
    }

    private function sameClinic(User $user, ?int $clinicId): bool
    {
        return $user->clinic_id !== null && $user->clinic_id === $clinicId;
    }
}
