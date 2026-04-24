<?php

namespace App\Domain\Clinic\Events;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClinicModuleActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Clinic $clinic,
        public readonly ModuleKey $moduleKey,
        public readonly User $activatedBy,
    ) {}
}
