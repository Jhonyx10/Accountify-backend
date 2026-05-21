<?php

namespace App\Support;

class PermissionCatalog
{
    public static function all(): array
    {
        $permissions = [];

        foreach (config('permissions.modules') as $subject => $label) {
            foreach (config('permissions.actions') as $action) {
                $permissions[] = "{$action} {$subject}";
            }
        }

        return $permissions;
    }

    public static function forCompanyAdmin(): array
    {
        $excluded = config('permissions.super_admin_only', []);

        return array_values(array_filter(
            static::all(),
            fn (string $permission) => ! static::permissionTargetsSubject($permission, $excluded)
        ));
    }

    public static function forStaff(): array
    {
        $excluded = array_merge(
            config('permissions.super_admin_only', []),
            ['users', 'roles', 'settings']
        );

        return array_values(array_filter(
            static::all(),
            fn (string $permission) => str_starts_with($permission, 'view ')
                && ! static::permissionTargetsSubject($permission, $excluded)
        ));
    }

    public static function forRole(string $roleName): array
    {
        $rolePermissions = config('permissions.roles', []);

        return match ($rolePermissions[$roleName] ?? null) {
            'all' => static::all(),
            'company' => static::forCompanyAdmin(),
            'staff' => static::forStaff(),
            default => [],
        };
    }

    private static function permissionTargetsSubject(string $permission, array $subjects): bool
    {
        foreach ($subjects as $subject) {
            if (str_ends_with($permission, " {$subject}"))
                return true;
        }

        return false;
    }
}
