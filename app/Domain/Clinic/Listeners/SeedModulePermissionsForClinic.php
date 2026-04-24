<?php

namespace App\Domain\Clinic\Listeners;

use App\Domain\Clinic\Events\ClinicModuleActivated;
use Illuminate\Support\Facades\Log;

class SeedModulePermissionsForClinic
{
    public function handle(ClinicModuleActivated $event): void
    {
        // TODO task 03: create module-specific permissions in Spatie teams context
        Log::info('module_activated', [
            'clinic_id' => $event->clinic->id,
            'module' => $event->moduleKey->value,
        ]);
    }
}
