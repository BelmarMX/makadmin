<?php

namespace App\Domain\Clinic\Models;

use App\Support\Tenancy\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ClinicBranch extends Model implements Auditable
{
    use BelongsToClinic;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'name',
        'address',
        'phone',
        'is_main',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
