<?php

namespace App\Support\Tenancy;

use App\Domain\Clinic\Models\Clinic;
use App\Support\Tenancy\Scopes\ClinicOrGlobalScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToClinicOrGlobal
{
    protected static function bootBelongsToClinicOrGlobal(): void
    {
        static::addGlobalScope(new ClinicOrGlobalScope);

        static::creating(function ($model) {
            if (! isset($model->clinic_id) && app()->bound('current.clinic')) {
                $model->clinic_id = app('current.clinic')->id;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Super admin creates a base (global) catalog entry.
     */
    public static function asGlobal(): Builder
    {
        if (! auth()->check() || ! auth()->user()->is_super_admin) {
            throw new \RuntimeException('asGlobal requires super admin');
        }

        return (new static)->newQueryWithoutScopes()->tap(function ($query) {
            $query->getModel()->clinic_id = null;
        });
    }
}
