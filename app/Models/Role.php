<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Traits\BelongsToCompany;

class Role extends SpatieRole
{
    
    protected $attributes = [
        'guard_name' => 'web',
    ];

    protected $fillable = [
        'name',
        'guard_name',
        'created_by',
    ];

    /**
     * Get the user who created the role.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            app(\Spatie\Permission\PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        );
    }
}
