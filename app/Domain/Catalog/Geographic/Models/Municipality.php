<?php

namespace App\Domain\Catalog\Geographic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    protected $fillable = ['state_id', 'name', 'inegi_code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function postalCodes(): HasMany
    {
        return $this->hasMany(PostalCode::class);
    }
}
