<?php

namespace App\Models\Traits;

use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * The "booting" method of the model.
     */
    public static function bootBelongsToCompany()
    {
        // Apply the Global Scope for filtering
        static::addGlobalScope(new CompanyScope);

        // Auto-inject created_by when creating
        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::user()->creatorId();
            }
        });
    }
}
