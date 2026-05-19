<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {

            $user = Auth::user();

            // 1. If the user is a Super Admin, skip the scoping entirely
            if ($user->type === 'super admin') {
                return;
            }

            // 2. Roles: include system template roles (created_by = 0) for Spatie lookups
            if ($model instanceof \App\Models\Role) {
                $builder->where(function ($q) use ($model, $user) {
                    $table = $model->getTable();
                    $q->where("{$table}.created_by", $user->creatorId())
                        ->orWhere("{$table}.created_by", 0);
                });

                return;
            }

            // 3. Apply the tenant filter for everyone else
            $builder->where($model->getTable() . '.created_by', $user->creatorId());
        }
    }
}
