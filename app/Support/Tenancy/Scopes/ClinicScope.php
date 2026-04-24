<?php

namespace App\Support\Tenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** @implements Scope<Model> */
class ClinicScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('current.clinic')) {
            return;
        }

        $builder->where(
            $model->getTable().'.clinic_id',
            app('current.clinic')->id
        );
    }
}
