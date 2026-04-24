<?php

namespace App\Support\Tenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** @implements Scope<Model> */
class ClinicOrGlobalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current.clinic')) {
            return;
        }

        $clinicId = app('current.clinic')->id;
        $table = $model->getTable();

        $builder->where(function (Builder $query) use ($table, $clinicId): void {
            $query->whereNull($table.'.clinic_id')
                ->orWhere($table.'.clinic_id', $clinicId);
        });
    }
}
