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
            
            // Super admins see everything
            if ($user->type === 'super admin') {
                return;
            }

            // Other users are scoped to their company (creator)
            $builder->where($model->getTable() . '.created_by', $user->creatorId());
        }
    }
}
