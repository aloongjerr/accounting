<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used by the accounting package. When multi-company
    | support is enabled, currency will be fetched from the companies table
    | if a company_id is attached. This serves as the fallback default.
    |
    */
    'currency' => env('ACCOUNTING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Fiscal Year
    |--------------------------------------------------------------------------
    |
    | Define the fiscal year start and end months. This is used for financial
    | report period calculations (Income Statement, Balance Sheet, etc.).
    |
    | Supported: 1-12 (January = 1, December = 12)
    |
    */
    'fiscal_year' => [
        'start_month' => 1,  // January
        'end_month' => 12,   // December
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

];
