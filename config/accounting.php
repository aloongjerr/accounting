<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;

return [

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for accounting tables. When null, the
    | default application database connection is used. Set this to a
    | configured connection name to store accounting data in a separate
    | database from your main application.
    |
    | Example: 'accounting' (must be defined in config/database.php)
    |
    */
    'connection' => env('ACCOUNTING_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used by the accounting package. When multi-tenant
    | support is enabled, currency will be fetched from the tenants table
    | if a tenant_id is attached. This serves as the fallback default.
    |
    */
    'currency' => env('ACCOUNTING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Fiscal Year
    |--------------------------------------------------------------------------
    |
    | Define the fiscal year start and end dates. This is used for financial
    | report period calculations (Income Statement, Balance Sheet, etc.).
    | The actual year is selected by the user at runtime.
    |
    | Example: Calendar year (Jan 1 - Dec 31) or custom (Jul 1 - Jun 30)
    |
    | Supported months: 1-12 (January = 1, December = 12)
    | Supported days: 1-31, or null for last day of month
    |
    */
    'fiscal_year' => [
        'start_month' => 1,   // January
        'start_day' => 1,
        'end_month' => 12,    // December
        'end_day' => null,    // null = last day of month
    ],

    /*
    |--------------------------------------------------------------------------
    | Account System Keys
    |--------------------------------------------------------------------------
    |
    | Register account system key enums here. The default AccountSystemKey
    | enum is always loaded. Custom enums must implement both Filament's
    | HasLabel and the HasAccountIdentity contract (parentKey + getCode methods).
    |
    | Example: App\Enums\CustomAccountKey::class
    |
    */
    'account_keys' => [
        AccountSystemKey::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot Driver
    |--------------------------------------------------------------------------
    |
    | The snapshot driver determines how account balances are retrieved or
    | cached for report generation. Different drivers optimize for different
    | hosting environments.
    |
    | Supported: "on_demand", "scheduled", "null"
    |
    | on_demand - Calculates on first request, stores in account_snapshots
    |             table. Subsequent requests served from DB. Works on
    |             shared hosting without cron jobs. (default)
    | scheduled - Pre-generates snapshots via artisan command + scheduler.
    |             Stores in account_snapshots table. Best for VPS/dedicated
    |             servers. Run: php artisan accounting:generate-snapshots
    | null      - Always calculates from entries. No persistence.
    |
    */
    'snapshot' => [
        'driver' => 'on_demand',
        'schedule_time' => '02:00', // Only used when driver is 'scheduled'
    ],

    /*
    |--------------------------------------------------------------------------
    | Immutable Data Policy
    |--------------------------------------------------------------------------
    |
    | Controls how accounting data (journals, entries) can be deleted.
    |
    | true  - No deletion allowed at all (hard delete blocked).
    |         Corrections must be made via void() or adjustment entries.
    |         This is the strictest and recommended for production.
    |
    | false - Soft deletes enabled. Records can be "deleted" but remain
    |         in the database with deleted_at timestamp. Useful for
    |         development/testing or when you need to recover data.
    |
    */
    'immutable' => true,

];
