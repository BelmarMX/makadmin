<?php

namespace App\Domain\Catalog\Veterinary\Policies;

use App\Domain\Catalog\Permissions;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VeterinaryCatalogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin || $user->can(Permissions::VIEW);
    }

    public function view(User $user, Model $entry): bool
    {
        return $user->is_super_admin || $user->can(Permissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin || $user->can(Permissions::CREATE);
    }

    public function update(User $user, Model $entry): bool
    {
        if ($entry->getAttribute('is_system') && ! $user->is_super_admin) {
            return false;
        }

        return $user->is_super_admin || $user->can(Permissions::UPDATE);
    }

    public function delete(User $user, Model $entry): bool
    {
        if ($entry->getAttribute('is_system') && ! $user->is_super_admin) {
            return false;
        }

        return $user->is_super_admin || $user->can(Permissions::ARCHIVE);
    }
}
