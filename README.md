# Laravel Accounting Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aloongjerr/accounting.svg?style=flat-square)](https://packagist.org/packages/aloongjerr/accounting)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aloongjerr/accounting/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aloongjerr/accounting/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aloongjerr/accounting.svg?style=flat-square)](https://packagist.org/packages/aloongjerr/accounting)

A comprehensive double-entry accounting package for Laravel with Filament v5 admin interface. Features automatic account mapping, immutable journal entries, financial reports, bank reconciliation, and multi-tenant support.

## Features

- **Double-Entry Bookkeeping** — Every transaction creates balanced debit/credit entries
- **Fluent Transaction API** — Intuitive builders for common transactions (received, paid, sold, purchased, transfer)
- **Auto-Mapping** — Automatically creates and resolves customer/supplier accounts via the `Accountable` interface
- **Immutable Journals** — Posted journals cannot be modified; corrections via void or adjustment entries
- **Chart of Accounts** — Pre-seeded hierarchical chart with 50+ accounts across 5 groups
- **Financial Reports** — Trial Balance, Income Statement, Balance Sheet
- **AR/AP Aging Report** — Track outstanding receivables and payables with age buckets
- **Budget vs Actual** — Compare budgeted amounts against actual spending with variance analysis
- **Bank Reconciliation** — Match bank statement lines against system entries
- **Snapshot System** — Optimized balance calculations with on-demand or scheduled caching
- **Multi-Tenant Ready** — Built-in tenant isolation for SaaS applications
- **Filament v5 UI** — Beautiful admin interface with resources, pages, and widgets

## Installation

```bash
composer require aloongjerr/accounting
```

> [!IMPORTANT]
> If you have not set up a custom theme, follow the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

Add the plugin's views to your theme CSS file:

```css
@source '../../../../vendor/aloongjerr/accounting/resources/**/*.blade.php';
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="accounting-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="accounting-config"
```

Seed the default chart of accounts:

```bash
php artisan db:seed --class="\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder"
```

## Filament Plugin Setup

Register the plugin in your Panel provider:

```php
use AloongJerr\Accounting\AccountingPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            AccountingPlugin::make()
                ->canCreateTransaction()  // Enable transaction creation UI
                ->manualTenantMode(),     // Enable if using multi-tenancy
        ]);
}
```

## Quick Start

### Basic Transactions

```php
use AloongJerr\Accounting\Facades\Accounting;

// Receive money (debit cash, credit AR)
Accounting::received(500000, 'Invoice payment')
    ->from($customer)
    ->toBank()
    ->commit();

// Pay money (credit cash, credit AP)
Accounting::paid(300000, 'Supplier payment')
    ->to($supplier)
    ->fromBank()
    ->commit();

// Record a sale (debit AR, credit revenue)
Accounting::sold(150000, 'Product sale')
    ->to($customer)
    ->commit();

// Record a purchase (debit expense, credit AP)
Accounting::purchased(200000, 'Equipment purchase')
    ->forExpense(AccountSystemKey::Equipment)
    ->from($supplier)
    ->commit();

// Transfer between accounts
Accounting::transfer(100000, 'Cash to bank')
    ->fromCash()
    ->toBank()
    ->commit();

// Manual journal entry
Accounting::journal('Depreciation entry')
    ->debit($depreciationExpense, 10000)
    ->credit($accumulatedDepreciation, 10000)
    ->commit();
```

### Working with Journals

```php
// Create and post in one go
$journal = Accounting::received(50000, 'Payment')
    ->from($customer)
    ->toCash()
    ->commit();

// Post the journal (makes it immutable)
$journal->post();

// Void a journal (requires remarks)
$journal->void('Customer requested cancellation');
```

### Auto-Mapping with Accountable Models

Make your models implement the `Accountable` interface:

```php
use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use AloongJerr\Accounting\Traits\HasAccountMapping;

class Customer extends Model implements Accountable, HasAccountIdentity
{
    use HasAccountMapping;

    protected function getAccountSystemKeys(): AccountSystemKey
    {
        return AccountSystemKey::AccountsReceivable;
    }
}

// Now use directly in transactions:
$customer = Customer::find(1);
Accounting::received(50000, 'Payment')->from($customer)->toCash()->commit();
// Automatically creates/credits "Customer 1 - Acme Corp" AR account
```

## Reports

```php
// Trial Balance
Accounting::trialBalance()
    ->asOf('2024-12-31')
    ->get();

// Income Statement
Accounting::incomeStatement()
    ->forPeriod('2024-01-01', '2024-12-31')
    ->get();

// Balance Sheet
Accounting::balanceSheet()
    ->asOf('2024-12-31')
    ->get();

// AR/AP Aging Report
Accounting::aging()
    ->forType(AccountSystemKey::AccountsReceivable)
    ->asOf(now())
    ->get();

// Budget vs Actual
Accounting::budgetReport()
    ->forPeriod('2024-01-01', '2024-12-31')
    ->get();

// Bank Reconciliation Report
Accounting::reconciliationReport($reconciliationId)
    ->get();
```

## Configuration

```php
return [
    // Separate database connection (optional)
    'connection' => env('ACCOUNTING_CONNECTION', null),

    // Default currency
    'currency' => env('ACCOUNTING_CURRENCY', 'USD'),

    // Fiscal year dates
    'fiscal_year' => [
        'start_month' => 1,   // January
        'start_day' => 1,
        'end_month' => 12,    // December
        'end_day' => null,    // null = last day of month
    ],

    // Snapshot driver: "on_demand", "scheduled", "null"
    'snapshot' => [
        'driver' => 'on_demand',
        'schedule_time' => '02:00',
    ],

    // Immutable data policy (recommended: true for production)
    'immutable' => true,
];
```

## Architecture

### Account Hierarchy

The chart of accounts uses a self-referential hierarchy:

```
Groups (Level 0)
├── Assets (1000)
│   ├── Current Assets (1100)
│   │   ├── Cash on Hand (1101)
│   │   ├── Cash in Bank (1102)
│   │   └── Accounts Receivable (1103)
│   └── Fixed Assets (1500)
│       ├── Equipment (1501)
│       └── Accumulated Depreciation (1505)
├── Liabilities (2000)
│   └── Current Liabilities (2100)
│       └── Accounts Payable (2101)
├── Equity (3000)
├── Revenue (4000)
└── Expenses (5000)
    └── Operating Expenses (5100)
        └── Rent Expense (5101)
```

### Auto-Mapping Resolver

When you pass an `Accountable` model to a transaction, the resolver:
1. Calls `getAccountKeys()` to determine the parent system account
2. Calls `getAccountIdentifier()` to get/create the leaf account
3. Creates the leaf account if it doesn't exist (e.g., "Customer 1 - Acme Corp")
4. Returns the resolved account for the journal entry

### Snapshot System

Account balances are cached in the `account_snapshots` table:

- **on_demand** (default): Calculates on first request, stores for subsequent reads
- **scheduled**: Pre-generates via `php artisan accounting:generate-snapshots`
- **null**: Always calculates from entries (no caching)

### Data Immutability

When `immutable` is `true`:
- Posted journals cannot be edited or deleted
- Corrections must use `void()` or adjustment entries
- Audit trail is preserved for compliance

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [aloongjerr](https://github.com/aloongjerr)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
