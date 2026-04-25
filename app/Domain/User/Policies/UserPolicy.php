<?php

namespace App\Domain\User\Policies;

use App\Domain\User\Permissions;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW);
    }

    public function view(User $user, User $model): bool
    {
        return $this->sameClinic($user, $model) && $user->can(Permissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::CREATE);
    }

    public function update(User $user, User $model): bool
    {
        return $this->sameClinic($user, $model) && $user->can(Permissions::UPDATE);
    }

    public function deactivate(User $user, User $model): bool
    {
        return $this->sameClinic($user, $model) && $user->id !== $model->id && $user->can(Permissions::DEACTIVATE);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->sameClinic($user, $model) && $user->can(Permissions::RESTORE);
    }

    public function manageRoles(User $user, User $model): bool
    {
        return $this->sameClinic($user, $model) && $user->can(Permissions::MANAGE_ROLES);
    }

    public function managePermissions(User $user, User $model): bool
    {
        return $this->sameClinic($user, $model) && $user->can(Permissions::MANAGE_PERMISSIONS);
    }

    private function sameClinic(User $user, User $model): bool
    {
        return $user->clinic_id !== null && $user->clinic_id === $model->clinic_id;
    }
}
