<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Events\ClinicModuleActivated;
use App\Domain\Clinic\Events\ClinicModuleDeactivated;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicModule;
use Illuminate\Validation\ValidationException;

class ToggleClinicModuleAction
{
    public function handle(Clinic $clinic, ModuleKey $moduleKey, bool $activate): ClinicModule
    {
        if ($activate) {
            return $this->activate($clinic, $moduleKey);
        }

        return $this->deactivate($clinic, $moduleKey);
    }

    private function activate(Clinic $clinic, ModuleKey $moduleKey): ClinicModule
    {
        foreach ($moduleKey->dependsOn() as $dep) {
            $depModule = ClinicModule::where('clinic_id', $clinic->id)
                ->where('module_key', $dep->value)
                ->first();

            if (! $depModule || ! $depModule->is_active) {
                $this->activateSingle($clinic, $dep);
            }
        }

        $module = $this->activateSingle($clinic, $moduleKey);

        ClinicModuleActivated::dispatch($clinic, $moduleKey, auth()->user());

        return $module;
    }

    private function deactivate(Clinic $clinic, ModuleKey $moduleKey): ClinicModule
    {
        $activeDependents = collect(ModuleKey::cases())
            ->filter(fn (ModuleKey $k) => in_array($moduleKey, $k->dependsOn(), true))
            ->filter(function (ModuleKey $k) use ($clinic): bool {
                $mod = ClinicModule::where('clinic_id', $clinic->id)
                    ->where('module_key', $k->value)
                    ->first();

                return $mod && $mod->is_active;
            });

        if ($activeDependents->isNotEmpty()) {
            $labels = $activeDependents->map(fn (ModuleKey $k) => $k->label())->join(', ');
            throw ValidationException::withMessages([
                'module' => "No se puede desactivar «{$moduleKey->label()}» porque depende de él: {$labels}.",
            ]);
        }

        $module = ClinicModule::where('clinic_id', $clinic->id)
            ->where('module_key', $moduleKey->value)
            ->firstOrFail();

        $module->update(['is_active' => false]);

        ClinicModuleDeactivated::dispatch($clinic, $moduleKey, auth()->user());

        return $module->fresh();
    }

    private function activateSingle(Clinic $clinic, ModuleKey $key): ClinicModule
    {
        return ClinicModule::updateOrCreate(
            ['clinic_id' => $clinic->id, 'module_key' => $key->value],
            ['is_active' => true, 'activated_at' => now(), 'activated_by' => auth()->id()],
        );
    }
}
