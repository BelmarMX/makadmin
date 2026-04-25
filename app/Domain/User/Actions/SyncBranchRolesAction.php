<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Enums\UserRole;
use App\Domain\User\Events\PermissionsChanged;
use App\Domain\User\Models\UserBranchRole;
use App\Domain\User\Permissions;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncBranchRolesAction
{
    /**
     * @param  list<array{branch_id: int, roles: list<string>}>  $assignments
     */
    public function handle(User $user, array $assignments): void
    {
        DB::transaction(function () use ($user, $assignments): void {
            $desired = collect($assignments)
                ->flatMap(fn (array $assignment): array => collect($assignment['roles'])
                    ->map(fn (string $role): array => [
                        'branch_id' => $assignment['branch_id'],
                        'role' => $role,
                    ])
                    ->all())
                ->unique(fn (array $assignment): string => "{$assignment['branch_id']}:{$assignment['role']}")
                ->values();

            UserBranchRole::where('user_id', $user->id)
                ->get()
                ->reject(fn (UserBranchRole $branchRole): bool => $desired
                    ->contains(fn (array $assignment): bool => $assignment['branch_id'] === $branchRole->branch_id && $assignment['role'] === $branchRole->role))
                ->each->delete();

            $roles = $desired
                ->map(function (array $assignment) use ($user): string {
                    UserRole::from($assignment['role']);

                    $branchRole = UserBranchRole::withTrashed()->updateOrCreate([
                        'clinic_id' => $user->clinic_id,
                        'user_id' => $user->id,
                        'branch_id' => $assignment['branch_id'],
                        'role' => $assignment['role'],
                    ]);

                    if ($branchRole->trashed()) {
                        $branchRole->restore();
                    }

                    return $assignment['role'];
                })
                ->unique()
                ->values()
                ->all();

            setPermissionsTeamId($user->clinic_id);

            foreach ($roles as $role) {
                $roleModel = Role::findOrCreate($role, 'web');

                if ($role === UserRole::ClinicAdmin->value) {
                    foreach (Permissions::all() as $permission) {
                        Permission::findOrCreate($permission, 'web');
                    }

                    $roleModel->syncPermissions(Permissions::all());
                }
            }

            $user->syncRoles($roles);
            $user->unsetRelation('roles');
            $user->unsetRelation('branchRoles');

            PermissionsChanged::dispatch($user, auth()->user());
        });
    }
}
