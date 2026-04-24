<?php

namespace App\Domain\Catalog\Veterinary\Models;

use App\Support\Tenancy\BelongsToClinicOrGlobal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PetSize extends Model implements Auditable
{
    use BelongsToClinicOrGlobal;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = ['clinic_id', 'name', 'weight_min_kg', 'weight_max_kg', 'sort_order', 'is_system', 'is_active'];

    protected $casts = [
        'weight_min_kg' => 'decimal:2',
        'weight_max_kg' => 'decimal:2',
        'sort_order' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];
}
