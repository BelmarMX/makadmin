<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Enums\UserRole;
use App\Domain\User\Events\PermissionsChanged;
use App\Domain\User\Events\RoleAssigned;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignRoleAction
{
    public function handle(User $user, string $role): void
    {
        UserRole::from($role);

        setPermissionsTeamId($user->clinic_id);

        $roleModel = Role::where('name', $role)->where('guard_name', 'web')->firstOrFail();
        $user->assignRole($roleModel);
        $user->unsetRelation('roles');

        RoleAssigned::dispatch($user, $role, auth()->user());
        PermissionsChanged::dispatch($user, auth()->user());
    }
}
