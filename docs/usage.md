# Usage Guide

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
  - [Fluent Configuration API](#fluent-configuration-api)
- [Transactions](#transactions)
  - [Receiving Money](#receiving-money)
  - [Paying Money](#paying-money)
  - [Recording Sales](#recording-sales)
  - [Recording Purchases](#recording-purchases)
  - [Transfers](#transfers)
  - [Adjustments](#adjustments)
  - [Manual Journal Entries](#manual-journal-entries)
- [Account Mapping](#account-mapping)
  - [Accountable Interface](#accountable-interface)
  - [HasAccountMapping Trait](#hasaccountmapping-trait)
  - [Custom Account Names](#custom-account-names)
- [Journals](#journals)
  - [Journal Lifecycle](#journal-lifecycle)
  - [Voiding Journals](#voiding-journals)
- [Ledger](#ledger)
  - [Account Ledger](#account-ledger)
  - [T-Account](#t-account)
- [Reports](#reports)
  - [Trial Balance](#trial-balance)
  - [Income Statement](#income-statement)
  - [Balance Sheet](#balance-sheet)
  - [AR/AP Aging Report](#arap-aging-report)
  - [Budget vs Actual](#budget-vs-actual)
  - [Bank Reconciliation](#bank-reconciliation)
- [Snapshots](#snapshots)
  - [On-Demand Driver](#on-demand-driver)
  - [Scheduled Driver](#scheduled-driver)
  - [Custom Schedule](#custom-schedule)
  - [Manual Generation](#manual-generation)
  - [Custom Drivers](#custom-drivers)
- [Events](#events)
- [Multi-Tenancy](#multi-tenancy)

---

## Installation

### Composer

```bash
composer require aloongjerr/accounting
```

### Custom Theme (Required)

This package requires a custom Filament theme for proper styling. If you haven't set one up, follow the [Filament Custom Theme Guide](https://filamentphp.com/docs/5.x/styling/overview#creating-a-custom-theme) first.

Import the package CSS into your theme file:

```css
/* resources/css/filament/admin/theme.css */
@import '../../../../../vendor/aloongjerr/accounting/resources/css/index.css';
```

### Artisan Install Command

The install command publishes config, migrations, runs migrations, and seeds the chart of accounts:

```bash
php artisan accounting:install
```

Or do it manually:

```bash
php artisan vendor:publish --tag="accounting-migrations"
php artisan migrate
php artisan vendor:publish --tag="accounting-config"
php artisan db:seed --class="\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder"
```

---

## Configuration

### Fluent Configuration API

Configure the package programmatically in your `AppServiceProvider`:

```php
use AloongJerr\Accounting\Facades\Accounting;

public function boot(): void
{
    Accounting::configure(function ($accounting) {
        $accounting->currency('MYR')
            ->connection('accounting')
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

Available configuration methods:

| Method | Description |
|--------|-------------|
| `currency(string)` | Set default currency |
| `connection(?string)` | Set database connection |
| `fiscalYear(...)` | Set fiscal year start/end |
| `snapshot(driver, scheduleTime)` | Set snapshot driver and time |
| `immutable(bool)` | Enable/disable immutability |
| `accountKeys(array)` | Register custom account key enums |
| `schedule(Closure)` | Customize scheduled tasks |

---

## Transactions

All transactions use the fluent builder pattern via the `Accounting` facade.

### Receiving Money

Records money received from a customer (debit cash/bank, credit accounts receivable).

```php
use AloongJerr\Accounting\Facades\Accounting;

// From a customer to bank
Accounting::received(500000, 'Invoice payment')
    ->from($customer)
    ->toBank()
    ->commit();

// From a customer to cash
Accounting::received(100000, 'Cash payment')
    ->from($customer)
    ->toCash()
    ->commit();

// From a specific account
Accounting::received(200000, 'Transfer received')
    ->from($customer)
    ->to($bankAccount)
    ->commit();

// Using system key directly
Accounting::received(150000, 'Payment')
    ->from($customer)
    ->toSystemKey(AccountSystemKey::CashInBank)
    ->commit();
```

### Paying Money

Records money paid to a supplier (debit accounts payable, credit cash/bank).

```php
// Pay supplier from bank
Accounting::paid(300000, 'Supplier payment')
    ->to($supplier)
    ->fromBank()
    ->commit();

// Pay supplier from cash
Accounting::paid(50000, 'Petty cash payment')
    ->to($supplier)
    ->fromCash()
    ->commit();

// Pay from specific account
Accounting::paid(200000, 'Payment')
    ->to($supplier)
    ->from($bankAccount)
    ->commit();
```

### Recording Sales

Records a sale on credit (debit accounts receivable, credit revenue).

```php
// Simple sale to customer
Accounting::sold(150000, 'Product sale')
    ->to($customer)
    ->commit();

// Sale with specific revenue account
Accounting::sold(200000, 'Service sale')
    ->to($customer)
    ->forRevenue(AccountSystemKey::ServiceRevenue)
    ->commit();
```

### Recording Purchases

Records a purchase on credit (debit expense/asset, credit accounts payable).

```php
// Purchase for expense
Accounting::purchased(100000, 'Office supplies')
    ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
    ->from($supplier)
    ->commit();

// Purchase for asset
Accounting::purchased(500000, 'Equipment purchase')
    ->forAsset(AccountSystemKey::Equipment)
    ->from($supplier)
    ->commit();

// Purchase for inventory
Accounting::purchased(300000, 'Inventory purchase')
    ->forAsset(AccountSystemKey::Inventory)
    ->from($supplier)
    ->commit();
```

### Transfers

Move money between accounts (debit destination, credit source).

```php
// Cash to bank
Accounting::transfer(100000, 'Deposit cash')
    ->fromCash()
    ->toBank()
    ->commit();

// Bank to cash (ATM withdrawal)
Accounting::transfer(50000, 'ATM withdrawal')
    ->fromBank()
    ->toCash()
    ->commit();

// Between specific accounts
Accounting::transfer(200000, 'Internal transfer')
    ->from($savingsAccount)
    ->to($checkingAccount)
    ->commit();

// Using system keys
Accounting::transfer(150000, 'Transfer')
    ->fromSystemKey(AccountSystemKey::CashOnHand)
    ->toSystemKey(AccountSystemKey::CashInBank)
    ->commit();
```

### Adjustments

Manual corrections with explicit debit and credit accounts.

```php
// Inventory correction
Accounting::adjustment(50000, 'Inventory adjustment')
    ->debit($inventoryAccount)
    ->credit($expenseAccount)
    ->commit();

// Fix an overpayment
Accounting::adjustment(10000, 'Payment correction')
    ->debit($bankAccount)
    ->credit($arAccount)
    ->commit();
```

### Manual Journal Entries

For transactions not covered by the builders.

```php
// Depreciation entry
Accounting::journal('Monthly depreciation')
    ->debit($depreciationExpense, 10000)
    ->credit($accumulatedDepreciation, 10000)
    ->commit();

// Multi-line journal entry
Accounting::journal('Payroll')
    ->debit($salaryExpense, 500000)
    ->debit($taxExpense, 100000)
    ->credit($cashInBank, 600000)
    ->commit();
```

---

## Account Mapping

### Accountable Interface

Implement the `Accountable` interface on your models to enable automatic account resolution:

```php
use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use AloongJerr\Accounting\Enums\AccountSystemKey;

class Customer extends Model implements Accountable, HasAccountIdentity
{
    public function getAccountKeys(array $data = []): AccountSystemKey
    {
        return AccountSystemKey::AccountsReceivable;
    }

    public function getAccountIdentifier(array $data = []): array
    {
        return [
            'id' => $this->id,
            'name' => $this->company_name,
        ];
    }
}
```

### HasAccountMapping Trait

The trait simplifies the interface implementation:

```php
use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use AloongJerr\Accounting\Traits\HasAccountMapping;
use AloongJerr\Accounting\Enums\AccountSystemKey;

class Customer extends Model implements Accountable, HasAccountIdentity
{
    use HasAccountMapping;

    protected function getAccountSystemKeys(): AccountSystemKey
    {
        return AccountSystemKey::AccountsReceivable;
    }
}

class Supplier extends Model implements Accountable, HasAccountIdentity
{
    use HasAccountMapping;

    protected function getAccountSystemKeys(): AccountSystemKey
    {
        return AccountSystemKey::AccountsPayable;
    }
}

// Dual-role entity (both customer and supplier)
class Partner extends Model implements Accountable, HasAccountIdentity
{
    use HasAccountMapping;

    protected function getAccountSystemKeys(): array
    {
        return [
            AccountSystemKey::AccountsReceivable,
            AccountSystemKey::AccountsPayable,
        ];
    }
}
```

### Custom Account Names

Override `getAccountName()` to customize the display name:

```php
class Customer extends Model implements Accountable, HasAccountIdentity
{
    use HasAccountMapping;

    protected function getAccountSystemKeys(): AccountSystemKey
    {
        return AccountSystemKey::AccountsReceivable;
    }

    protected function getAccountName(): string
    {
        return $this->company_name; // Use company_name instead of name
    }
}
```

---

## Journals

### Journal Lifecycle

```
Draft → Posted → (Immutable)
  ↓
Voided
```

```php
// Create a journal (starts as draft)
$journal = Accounting::received(50000, 'Payment')
    ->from($customer)
    ->toCash()
    ->commit();

// Post the journal (makes it immutable)
$journal->post();

// Posted journals cannot be modified
// $journal->description = 'Changed'; // Throws exception
// $journal->save(); // Throws exception

// Check journal status
$journal->isDraft();    // false
$journal->isPosted();   // true
$journal->isVoid();     // false
```

### Voiding Journals

To correct a posted journal, void it with remarks:

```php
$journal->void('Customer requested cancellation');

// Voided journals are immutable
// A reversing entry is NOT automatically created
// You must create a new entry for corrections
```

---

## Ledger

### Account Ledger

View all entries for a single account with running balance:

```php
// Get ledger for an account
$ledger = Accounting::ledger(AccountSystemKey::CashInBank)
    ->forPeriod(Carbon::parse('2024-01-01'), Carbon::parse('2024-12-31'))
    ->forTenant($tenantId); // optional

// Opening balance (cumulative before period)
$ledger->getOpeningBalance(); // int (in cents)

// Closing balance (cumulative through period end)
$ledger->getClosingBalance(); // int (in cents)

// Total debits/credits for the period
$ledger->getTotalDebit();
$ledger->getTotalCredit();

// Get all entries with running balance
$entries = $ledger->getEntries();

foreach ($entries as $entry) {
    echo $entry->date;
    echo $entry->description;
    echo $entry->debit;
    echo $entry->credit;
    echo $entry->runningBalance;
    echo $entry->journal->description;
}
```

### T-Account

Traditional T-account layout with debits on the left, credits on the right:

```php
// Get T-account view
$tAccount = Accounting::tAccount(AccountSystemKey::AccountsReceivable)
    ->forPeriod(Carbon::parse('2024-01-01'), Carbon::parse('2024-12-31'));

// Debit side (left)
$debits = $tAccount->getDebitEntries();

// Credit side (right)
$credits = $tAccount->getCreditEntries();

// Totals
$tAccount->getTotalDebit();
$tAccount->getTotalCredit();

// Balances
$tAccount->getOpeningBalance();
$tAccount->getClosingBalance();
```

---

## Reports

### Trial Balance

Shows all accounts with their balances at a point in time.

```php
use AloongJerr\Accounting\Facades\Accounting;

$rows = Accounting::trialBalance()
    ->asOf('2024-12-31')
    ->get();

foreach ($rows as $row) {
    echo $row->account->name;
    echo $row->debit;
    echo $row->credit;
}
```

### Income Statement

Shows revenue and expenses for a period.

```php
$rows = Accounting::incomeStatement()
    ->forPeriod('2024-01-01', '2024-12-31')
    ->get();

// Returns rows grouped by:
// - Operating Revenue
// - Non-Operating Revenue
// - Cost of Goods Sold
// - Operating Expenses
// - Non-Operating Expenses
```

### Balance Sheet

Shows assets, liabilities, and equity at a point in time.

```php
$rows = Accounting::balanceSheet()
    ->asOf('2024-12-31')
    ->get();

// Returns rows grouped by:
// - Assets (Current, Fixed)
// - Liabilities (Current, Long-term)
// - Equity
```

### AR/AP Aging Report

Track outstanding receivables and payables with age buckets.

```php
// Accounts Receivable aging
$rows = Accounting::aging()
    ->forType(AccountSystemKey::AccountsReceivable)
    ->asOf(now())
    ->get();

// Accounts Payable aging
$rows = Accounting::aging()
    ->forType(AccountSystemKey::AccountsPayable)
    ->asOf(now())
    ->get();

// Each row contains:
// - account_id, account_name
// - current (0-30 days)
// - days_31_60
// - days_61_90
// - over_90
// - total
```

### Budget vs Actual

Compare budgeted amounts against actual spending.

```php
// Create a budget
use AloongJerr\Accounting\Models\Budget;

Budget::create([
    'account_id' => $expenseAccount->id,
    'period_start' => '2024-01-01',
    'period_end' => '2024-12-31',
    'amount' => 1200000, // 12,000.00 in cents
    'currency' => 'USD',
]);

// Generate report
$rows = Accounting::budgetReport()
    ->forPeriod('2024-01-01', '2024-12-31')
    ->get();

// Each row contains:
// - account_id, account_name, account_code
// - budgeted_amount
// - actual_amount
// - variance (budgeted - actual)
// - variance_percentage
```

### Bank Reconciliation

Match bank statement lines against system journal entries.

```php
use AloongJerr\Accounting\Models\Reconciliation;
use AloongJerr\Accounting\Models\BankStatementLine;

// Create a reconciliation session
$reconciliation = Reconciliation::create([
    'account_id' => $bankAccount->id,
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'opening_balance' => 1000000, // 10,000.00 in cents
]);

// Add bank statement lines
BankStatementLine::create([
    'reconciliation_id' => $reconciliation->id,
    'transaction_date' => '2024-01-15',
    'description' => 'Customer payment',
    'amount' => 500000, // Positive = credit (from bank perspective)
    'type' => 'credit',
]);

// Match a bank line to a system journal entry
$bankLine->matchTo($journalEntry->id);

// Unmatch
$bankLine->unmatch();

// Get reconciliation report
$report = Accounting::reconciliationReport($reconciliation->id);
$rows = $report->get();
$summary = $report->summary();

// Summary contains:
// - bank_balance
// - system_balance
// - difference
// - is_balanced (true if difference == 0)
// - matched_count, unmatched_bank_count, unmatched_system_count

// Complete the reconciliation
$reconciliation->complete();
```

---

## Snapshots

The snapshot system optimizes balance calculations by caching results.

### On-Demand Driver (Default)

Calculates on first request, stores in `account_snapshots` table. Subsequent requests served from DB.

```php
// In config/accounting.php
'snapshot' => [
    'driver' => 'on_demand',
],
```

No cron job required. Works on shared hosting.

### Scheduled Driver

Pre-generates snapshots via artisan command + scheduler. Best for VPS/dedicated servers.

```php
// In config/accounting.php
'snapshot' => [
    'driver' => 'scheduled',
    'schedule_time' => '02:00',
],
```

The schedule is auto-registered when `driver` is `scheduled`. By default, it runs `accounting:generate-snapshots` daily at the configured `schedule_time`.

### Custom Schedule

Override the default schedule using the fluent configuration API:

```php
use AloongJerr\Accounting\Facades\Accounting;

// In AppServiceProvider::boot()
Accounting::configure(function ($accounting) {
    $accounting->snapshot(driver: 'scheduled', scheduleTime: '00:10')
        ->schedule(function ($schedule) {
            // Full control over the schedule
            $schedule->command('accounting:generate-snapshots')
                ->dailyAt('00:10')
                ->evenInMaintenanceMode()
                ->withoutOverlapping();
        });
});
```

When a custom `schedule()` callback is provided, it receives the `Schedule` instance directly and the default schedule is not registered.

### Manual Generation

```bash
# Generate for today
php artisan accounting:generate-snapshots

# Generate for a specific date
php artisan accounting:generate-snapshots --date=2024-12-31

# Generate for a date range
php artisan accounting:generate-snapshots --from=2024-01-01 --to=2024-12-31

# Generate for a specific tenant
php artisan accounting:generate-snapshots --tenant=1
```

### Custom Drivers

Register your own snapshot driver via `SnapshotManager::extend()`:

```php
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Snapshots\Contracts\SnapshotDriver;

// In AppServiceProvider::boot()
Accounting::snapshot()->extend('redis', function ($balanceService) {
    return new RedisSnapshotDriver($balanceService);
});
```

---

## Events

All transactions dispatch events after successful commit:

| Transaction | Event |
|-------------|-------|
| `received()` | `JournalReceivedEvent` |
| `paid()` | `JournalPaidEvent` |
| `sold()` | `JournalSoldEvent` |
| `purchased()` | `JournalPurchasedEvent` |
| `transfer()` | `JournalTransferredEvent` |
| `adjustment()` | `JournalAdjustmentEvent` |
| `journal()` | `JournalCreatedEvent` |

All events contain the `Journal` model:

```php
use AloongJerr\Accounting\Events\JournalReceivedEvent;
use Illuminate\Support\Facades\Event;

Event::listen(JournalReceivedEvent::class, function ($event) {
    $journal = $event->journal;
    // Send notification, update external system, etc.
});
```

---

## Multi-Tenancy

All transactions and reports support tenant isolation.

```php
// Set tenant on transaction
Accounting::received(50000, 'Payment')
    ->from($customer)
    ->toBank()
    ->tenantId($tenantId)
    ->commit();

// Filter reports by tenant
$rows = Accounting::trialBalance()
    ->forTenant($tenantId)
    ->asOf('2024-12-31')
    ->get();

// Models have tenant_id column
$journal->tenant_id = $tenantId;
```
