<?php

namespace App\Domain\Catalog\Geographic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['iso2', 'iso3', 'name', 'phone_code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
