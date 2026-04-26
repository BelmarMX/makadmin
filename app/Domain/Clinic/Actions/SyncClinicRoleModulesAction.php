<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicRoleModule;
use Illuminate\Support\Facades\DB;

class SyncClinicRoleModulesAction
{
    /**
     * @param  list<string>  $enabledModuleKeys
     */
    public function handle(Clinic $clinic, string $role, array $enabledModuleKeys): void
    {
        DB::transaction(function () use ($clinic, $role, $enabledModuleKeys): void {
            ClinicRoleModule::where('clinic_id', $clinic->id)
                ->where('role', $role)
                ->get()
                ->reject(fn (ClinicRoleModule $module): bool => in_array($module->module_key, $enabledModuleKeys, true))
                ->each
                ->delete();

            foreach (ModuleKey::cases() as $module) {
                $roleModule = ClinicRoleModule::withTrashed()->updateOrCreate([
                    'clinic_id' => $clinic->id,
                    'role' => $role,
                    'module_key' => $module->value,
                ], [
                    'is_enabled' => in_array($module->value, $enabledModuleKeys, true),
                ]);

                if ($roleModule->trashed()) {
                    $roleModule->restore();
                }
            }
        });
    }
}
