<?php

namespace App\Support\Tenancy;

use App\Domain\Clinic\Models\Clinic;
use App\Support\Tenancy\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToClinic
{
    protected static function bootBelongsToClinic(): void
    {
        static::addGlobalScope(new ClinicScope);

        static::creating(function ($model) {
            if (! $model->clinic_id && app()->bound('current.clinic')) {
                $model->clinic_id = app('current.clinic')->id;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public static function withoutTenancy(\Closure $callback): mixed
    {
        if (! auth()->check() || ! auth()->user()->is_super_admin) {
            throw new \RuntimeException('withoutTenancy requires super admin');
        }

        return static::withoutGlobalScope(ClinicScope::class)->tap($callback);
    }
}
