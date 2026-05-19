<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Maps legacy ERP permission names to the canonical catalog used by the Vue frontend.
 */
class PermissionNormalizer
{
    /** Permissions excluded from API/CASL — dashboard is open to all authenticated users */
    private const EXCLUDED = ['view dashboard', 'manage dashboard'];

    /** @var array<string, list<string>> */
    private const LEGACY_MAP = [
        'manage user' => ['manage users', 'view users'],
        'manage role' => ['manage roles', 'view roles'],
        'manage permission' => ['manage roles', 'view roles'],
        'manage company' => ['manage companies', 'view companies'],
        'manage product' => ['manage products', 'view products'],
        'manage customer' => ['manage customers', 'view customers'],
        'manage vendor' => ['manage vendors', 'view vendors'],
        'manage proposal' => ['manage proposals', 'view proposals'],
        'manage retainer' => ['manage retainers', 'view retainers'],
        'manage invoice' => ['manage invoices', 'view invoices'],
        'manage bill' => ['manage bills', 'view bills'],
        'manage chart of account' => ['manage chart of accounts', 'view chart of accounts'],
        'manage bank account' => ['manage bank accounts', 'view bank accounts'],
        'manage transfer' => ['manage transfers', 'view transfers'],
        'manage credit note' => ['manage credit notes', 'view credit notes'],
        'manage debit note' => ['manage debit notes', 'view debit notes'],
        'manage asset' => ['manage assets', 'view assets'],
        'manage contract' => ['manage contracts', 'view contracts'],
        'manage report' => ['manage reports', 'view reports'],
        'manage custom field' => ['manage custom fields', 'view custom fields'],
        'manage budget' => ['manage budgets', 'view budgets'],
        'manage referral' => ['manage referral program', 'view referral program'],
        'manage setting' => ['manage settings', 'view settings'],
        'view report' => ['view reports'],
        'view invoice' => ['view invoices'],
        'create invoice' => ['manage invoices', 'view invoices'],
        'edit invoice' => ['manage invoices', 'view invoices'],
        'delete invoice' => ['manage invoices'],
        'view bill' => ['view bills'],
        'create bill' => ['manage bills', 'view bills'],
        'edit bill' => ['manage bills', 'view bills'],
        'delete bill' => ['manage bills'],
    ];

    public static function expand(Collection|array $permissionNames): array
    {
        $expanded = [];

        foreach (Collection::wrap($permissionNames) as $name) {
            if ($name === '$str' || $name === null || $name === '') {
                continue;
            }

            if (isset(self::LEGACY_MAP[$name])) {
                $expanded = array_merge($expanded, self::LEGACY_MAP[$name]);
                continue;
            }

            // Already canonical or unknown — pass through
            $expanded[] = $name;
        }

        return static::withoutExcluded(array_values(array_unique($expanded)));
    }

    private static function withoutExcluded(array $permissions): array
    {
        return array_values(array_filter(
            $permissions,
            fn (string $name) => !in_array($name, self::EXCLUDED, true)
        ));
    }

    public static function forUser($user): array
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user->loadMissing(['roles.permissions', 'permissions']);

        $raw = $user->getAllPermissions()->pluck('name');

        return static::expand($raw);
    }
}
