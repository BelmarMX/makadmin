<?php

namespace App\Domain\Patient\Events;

use App\Domain\Patient\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final class PatientCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Patient $patient,
        public readonly ?User $actor,
    ) {}
}
