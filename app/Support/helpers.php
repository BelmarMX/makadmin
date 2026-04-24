<?php

use App\Domain\Clinic\Models\Clinic;

if (! function_exists('current_clinic')) {
    function current_clinic(): ?Clinic
    {
        return app()->bound('current.clinic') ? app('current.clinic') : null;
    }
}
