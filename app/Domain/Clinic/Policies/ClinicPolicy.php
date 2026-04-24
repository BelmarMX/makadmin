<?php

namespace App\Domain\Clinic\Policies;

use App\Domain\Clinic\Models\Clinic;
use App\Models\User;

class ClinicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function view(User $user, Clinic $clinic): bool
    {
        return $user->is_super_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function update(User $user, Clinic $clinic): bool
    {
        return $user->is_super_admin;
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        return $user->is_super_admin;
    }

    public function manageModules(User $user, Clinic $clinic): bool
    {
        return $user->is_super_admin;
    }

    public function manageBranches(User $user, Clinic $clinic): bool
    {
        return $user->is_super_admin;
    }
}
