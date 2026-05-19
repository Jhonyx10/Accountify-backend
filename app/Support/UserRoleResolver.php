<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

class UserRoleResolver
{
    /**
     * Ensure the user has a Spatie role so permissions resolve from role_has_permissions.
     */
    public static function ensureDefaultRole(User $user): void
    {
        if ($user->roles()->count() > 0) {
            return;
        }

        $roleName = match ($user->type) {
            'super admin' => 'super admin',
            'company' => self::findRoleName(['company', 'Company', 'company admin', 'Administrator']),
            default => self::findRoleName(['staff', 'Staff', 'Staff 2']),
        };

        if (!$roleName) {
            return;
        }

        $role = Role::withoutGlobalScopes()->where('name', $roleName)->first();

        if ($role) {
            $user->assignRole($role);
        }
    }

    private static function findRoleName(array $candidates): ?string
    {
        foreach ($candidates as $name) {
            if (Role::withoutGlobalScopes()->where('name', $name)->exists()) {
                return $name;
            }
        }

        return null;
    }
}
