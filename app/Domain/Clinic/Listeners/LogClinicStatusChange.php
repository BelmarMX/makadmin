<?php

namespace App\Domain\Clinic\Listeners;

use App\Domain\Clinic\Events\ClinicActivated;
use App\Domain\Clinic\Events\ClinicDeactivated;
use Illuminate\Support\Facades\Log;

class LogClinicStatusChange
{
    public function handle(ClinicActivated|ClinicDeactivated $event): void
    {
        $action = $event instanceof ClinicActivated ? 'activated' : 'deactivated';

        Log::channel('security')->info("clinic_{$action}", [
            'clinic_id' => $event->clinic->id,
            'clinic_slug' => $event->clinic->slug,
            'by_user_id' => $event->activatedBy->id ?? $event->deactivatedBy->id,
        ]);
    }
}
