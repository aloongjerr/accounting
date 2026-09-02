# Laravel Accounting Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aloongjerr/accounting.svg?style=flat-square)](https://packagist.org/packages/aloongjerr/accounting)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aloongjerr/accounting/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aloongjerr/accounting/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aloongjerr/accounting.svg?style=flat-square)](https://packagist.org/packages/aloongjerr/accounting)

A comprehensive double-entry accounting package for Laravel with Filament v5 admin interface. Features automatic account mapping, immutable journal entries, financial reports, bank reconciliation, and multi-tenant support.

## Features

- **Double-Entry Bookkeeping** — Every transaction creates balanced debit/credit entries
- **Fluent Transaction API** — Intuitive builders for common transactions (received, paid, sold, purchased, transfer, adjustment)
- **Auto-Mapping** — Automatically creates and resolves customer/supplier accounts via the `Accountable` interface
- **Immutable Journals** — Posted journals cannot be modified; corrections via void or adjustment entries
- **Chart of Accounts** — Pre-seeded hierarchical chart with 50+ accounts across 5 groups
- **Financial Reports** — Trial Balance, Income Statement, Balance Sheet
- **AR/AP Aging Report** — Track outstanding receivables and payables with age buckets
- **Budget vs Actual** — Compare budgeted amounts against actual spending with variance analysis
- **Bank Reconciliation** — Match bank statement lines against system entries
- **Snapshot System** — Optimized balance calculations with on-demand, scheduled, or custom caching drivers
- **Ledger & T-Account** — Per-account ledger views with running balances
- **Fluent Configuration** — Programmatic runtime configuration with custom schedule support
- **Artisan Commands** — Install command and snapshot generation
- **Event System** — Events dispatched for all transaction types
- **Multi-Tenant Ready** — Built-in tenant isolation for SaaS applications
- **Filament v5 UI** — Beautiful admin interface with resources, pages, and widgets

## Installation

```bash
composer require aloongjerr/accounting
```

> [!IMPORTANT]
> This package requires a custom Filament theme. If you haven't set one up, follow the [Filament Custom Theme Guide](https://filamentphp.com/docs/5.x/styling/overview#creating-a-custom-theme) first.

Import the package CSS into your theme file:

```css
/* resources/css/filament/admin/theme.css */
@import '../../../../vendor/aloongjerr/accounting/**/*';
```

### Quick Install (Recommended)

```bash
php artisan accounting:install
```

This publishes config, migrations, runs migrations, and seeds the chart of accounts.

You will be prompted to select the database connection. The default value is your app's default database connection. To specify directly:

```bash
php artisan accounting:install --connection=accounting
```

> [!NOTE]
> All accounting migrations use the connection from `config('accounting.connection')`. If left empty, the app's default connection is used.

### Manual Install

```bash
php artisan vendor:publish --tag="accounting-config"
php artisan vendor:publish --tag="accounting-migrations"
php artisan migrate
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

// Adjustment (explicit debit/credit)
Accounting::adjustment(50000, 'Inventory correction')
    ->debit($inventoryAccount)
    ->credit($expenseAccount)
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

## Ledger & T-Account

```php
// Account Ledger with running balance
$ledger = Accounting::ledger(AccountSystemKey::CashInBank)
    ->forPeriod(Carbon::parse('2024-01-01'), Carbon::parse('2024-12-31'));

$ledger->getOpeningBalance();  // Cumulative before period
$ledger->getClosingBalance();  // Cumulative through period end
$ledger->getTotalDebit();
$ledger->getTotalCredit();
$entries = $ledger->getEntries(); // With running balance

// T-Account (debits left, credits right)
$tAccount = Accounting::tAccount(AccountSystemKey::AccountsReceivable)
    ->forPeriod(Carbon::parse('2024-01-01'), Carbon::parse('2024-12-31'));

$tAccount->getDebitEntries();
$tAccount->getCreditEntries();
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

## Multi-Tenancy

The package supports multi-tenant isolation via `tenant_id`. Each tenant has its own chart of accounts, journals, and reports.

### Setting Current Tenant

Use the singleton to set the current tenant (e.g., in middleware):

```php
use AloongJerr\Accounting\Facades\Accounting;

// In middleware
public function handle($request, Closure $next)
{
    $user = auth()->user();
    Accounting::setTenant($user->tenant_id);
    
    return $next($request);
}
```

### Using Tenant in Transactions

Transactions automatically use the current tenant from the singleton:

```php
// Uses tenant from singleton (set in middleware)
Accounting::received(50000, 'Payment')
    ->from($customer)
    ->toBank()
    ->commit();
```

Override explicitly when needed:

```php
// Explicit tenant override
Accounting::received(50000, 'Platform fee')
    ->forTenant(null)  // Platform-level (no tenant)
    ->commit();
```

### Getting Current Tenant

```php
$tenantId = Accounting::getTenant();
```

### Tenant-Scoped Reports

```php
// Report for current tenant (from singleton)
Accounting::trialBalance()
    ->asOf('2024-12-31')
    ->get();

// Report for specific tenant
Accounting::trialBalance()
    ->forTenant($tenantId)
    ->asOf('2024-12-31')
    ->get();
```

## Configuration

### Config File

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

### Fluent Configuration API

Configure programmatically in your `AppServiceProvider`:

```php
use AloongJerr\Accounting\Facades\Accounting;

public function boot(): void
{
    Accounting::configure(function ($accounting) {
        $accounting->currency('MYR')
            ->fiscalYear(startMonth: 7, startDay: 1, endMonth: 6, endDay: 30)
            ->snapshot(driver: 'scheduled', scheduleTime: '00:10')
            ->immutable(true)
            ->schedule(function ($schedule) {
                $schedule->command('accounting:generate-snapshots')
                    ->dailyAt('00:10')
                    ->evenInMaintenanceMode();
            });
    });
}
```

## Artisan Commands

```bash
# Install: publish config, migrations, run migrations, seed chart of accounts
php artisan accounting:install
php artisan accounting:install --connection=accounting  # Use a specific connection

# Generate snapshots
php artisan accounting:generate-snapshots                    # Today
php artisan accounting:generate-snapshots --date=2024-12-31  # Specific date
php artisan accounting:generate-snapshots --from=2024-01-01 --to=2024-12-31
php artisan accounting:generate-snapshots --tenant=1
```

## Events

All transactions dispatch events after commit:

| Transaction | Event |
|-------------|-------|
| `received()` | `JournalReceivedEvent` |
| `paid()` | `JournalPaidEvent` |
| `sold()` | `JournalSoldEvent` |
| `purchased()` | `JournalPurchasedEvent` |
| `transfer()` | `JournalTransferredEvent` |
| `adjustment()` | `JournalAdjustmentEvent` |
| `journal()` | `JournalCreatedEvent` |

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
