<?php

/**
 * Canonical permission catalog for Accountify.
 *
 * Naming: "{action} {subject}" (first token = CASL action, remainder = CASL subject).
 * Actions: view (read/list), manage (create/update/delete).
 */
return [

    'actions' => ['view', 'manage'],

    'modules' => [
        'users' => 'Users',
        'roles' => 'Roles',
        'companies' => 'Companies',
        'products' => 'Products',
        'product stock' => 'Product Stock',
        'customers' => 'Customers',
        'vendors' => 'Vendors',
        'proposals' => 'Proposals',
        'retainers' => 'Retainers',
        'bank accounts' => 'Bank Accounts',
        'transfers' => 'Transfers',
        'invoices' => 'Invoices',
        'revenues' => 'Revenues',
        'credit notes' => 'Credit Notes',
        'purchase orders' => 'Purchase Orders',
        'bills' => 'Bills',
        'payments' => 'Payments',
        'debit notes' => 'Debit Notes',
        'chart of accounts' => 'Chart of Accounts',
        'journal entries' => 'Journal Entries',
        'write checks' => 'Write Checks',
        'budgets' => 'Budgets',
        'contracts' => 'Contracts',
        'assets' => 'Assets',
        'plans' => 'Plans',
        'plan requests' => 'Plan Requests',
        'coupons' => 'Coupons',
        'orders' => 'Orders',
        'email templates' => 'Email Templates',
        'notification templates' => 'Notification Templates',
        'reports' => 'Reports',
        'taxes' => 'Taxes',
        'categories' => 'Categories',
        'units' => 'Units',
        'custom fields' => 'Custom Fields',
        'contract types' => 'Contract Types',
        'landing page' => 'Landing Page',
        'referral program' => 'Referral Program',
        'settings' => 'Settings',
    ],

    /** Modules only assignable to super admin (platform-level). */
    'super_admin_only' => [
        'companies',
        'plans',
        'plan requests',
        'coupons',
        'orders',
        'email templates',
        'landing page',
    ],

    'roles' => [
        'super admin' => 'all',
        'company' => 'company',
        'staff' => 'staff',
    ],

];
