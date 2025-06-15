<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ArchivedScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // $builder->whereNull('archived_at');
        $builder->whereNull($model->getTable() . '.archived_at');
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withArchived', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('onlyArchived', function (Builder $builder) {
            $model = $builder->getModel();
            return $builder->withoutGlobalScope($this)->whereNotNull($model->getTable() . '.archived_at');
        });
    }
}
