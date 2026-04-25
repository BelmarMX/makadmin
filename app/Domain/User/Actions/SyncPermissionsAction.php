<?php

namespace App\Domain\User\Actions;

use App\Domain\Clinic\Models\ClinicModule;
use App\Domain\User\Events\PermissionsChanged;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class SyncPermissionsAction
{
    /**
     * @param  array<string, list<string>>  $permissionsByModule
     */
    public function handle(User $user, array $permissionsByModule): void
    {
        DB::transaction(function () use ($user, $permissionsByModule): void {
            setPermissionsTeamId($user->clinic_id);

            $permissions = $this->requestedPermissions($user, $permissionsByModule);

            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            $user->syncPermissions($permissions);
            $user->unsetRelation('permissions');

            PermissionsChanged::dispatch($user, auth()->user());

            Log::channel('security')->info('user_permissions_synced', [
                'clinic_id' => $user->clinic_id,
                'user_id' => $user->id,
                'by_user_id' => auth()->id(),
                'permissions' => $permissions,
            ]);
        });
    }

    /**
     * @param  array<string, list<string>>  $permissionsByModule
     * @return list<string>
     */
    private function requestedPermissions(User $user, array $permissionsByModule): array
    {
        $activeModules = ClinicModule::withoutGlobalScopes()
            ->where('clinic_id', $user->clinic_id)
            ->where('is_active', true)
            ->pluck('module_key');

        return $activeModules
            ->flatMap(fn (string $module): Collection => collect($permissionsByModule[$module] ?? [])
                ->filter(fn (string $action): bool => in_array($action, ['view', 'create', 'update', 'delete'], true))
                ->map(fn (string $action): string => "{$module}.{$action}"))
            ->values()
            ->all();
    }
}
