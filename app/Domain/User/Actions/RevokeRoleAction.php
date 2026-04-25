<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Enums\UserRole;
use App\Domain\User\Events\PermissionsChanged;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RevokeRoleAction
{
    public function handle(User $user, string $role): void
    {
        UserRole::from($role);

        setPermissionsTeamId($user->clinic_id);

        $roleModel = Role::where('name', $role)->where('guard_name', 'web')->firstOrFail();
        $user->removeRole($roleModel);
        $user->unsetRelation('roles');

        PermissionsChanged::dispatch($user, auth()->user());
    }
}
