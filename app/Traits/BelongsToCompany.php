<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    public static function bootBelongsToCompany()
    {
        // 1. Automatically apply the Global Scope for queries
        static::addGlobalScope(new CompanyScope);

        // 2. Automatically inject created_by on record creation
        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::user()->creatorId();
            }
        });
    }
}