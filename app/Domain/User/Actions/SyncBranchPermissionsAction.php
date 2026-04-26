<?php

namespace App\Domain\User\Actions;

use App\Domain\Clinic\Models\ClinicModule;
use App\Domain\User\Events\PermissionsChanged;
use App\Domain\User\Models\UserBranchPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncBranchPermissionsAction
{
    /**
     * @param  array<string, list<string>>  $permissionsByModule
     */
    public function handle(User $user, int $branchId, array $permissionsByModule): void
    {
        DB::transaction(function () use ($user, $branchId, $permissionsByModule): void {
            $activeModules = ClinicModule::withoutGlobalScopes()
                ->where('clinic_id', $user->clinic_id)
                ->where('is_active', true)
                ->pluck('module_key');

            $permissions = $activeModules
                ->flatMap(fn (string $module) => collect($permissionsByModule[$module] ?? [])
                    ->filter(fn (string $action): bool => in_array($action, ['view', 'create', 'update', 'delete'], true))
                    ->map(fn (string $action): string => "{$module}.{$action}"))
                ->values()
                ->all();

            UserBranchPermission::where('user_id', $user->id)
                ->where('branch_id', $branchId)
                ->delete();

            foreach ($permissions as $permission) {
                UserBranchPermission::create([
                    'clinic_id' => $user->clinic_id,
                    'user_id' => $user->id,
                    'branch_id' => $branchId,
                    'permission' => $permission,
                ]);
            }

            PermissionsChanged::dispatch($user, auth()->user());

            Log::channel('security')->info('user_branch_permissions_synced', [
                'clinic_id' => $user->clinic_id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'by_user_id' => auth()->id(),
                'permissions' => $permissions,
            ]);
        });
    }
}
