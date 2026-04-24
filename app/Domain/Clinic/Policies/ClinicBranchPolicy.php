<?php

namespace App\Domain\Clinic\Policies;

use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;

class ClinicBranchPolicy
{
    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function update(User $user, ClinicBranch $branch): bool
    {
        return $user->is_super_admin;
    }

    public function delete(User $user, ClinicBranch $branch): bool
    {
        return $user->is_super_admin && ! $branch->is_main;
    }
}
