<?php

namespace App\Domain\Catalog\Veterinary\Models;

use App\Support\Tenancy\BelongsToClinicOrGlobal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Species extends Model implements Auditable
{
    use BelongsToClinicOrGlobal;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = ['clinic_id', 'name', 'slug', 'icon', 'sort_order', 'is_system', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }
}
