<?php

namespace App\Domain\Catalog\Veterinary\Models;

use App\Support\Tenancy\BelongsToClinicOrGlobal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PelageColor extends Model implements Auditable
{
    use BelongsToClinicOrGlobal;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = ['clinic_id', 'name', 'hex', 'is_system', 'is_active'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];
}
