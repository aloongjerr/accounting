# Architecture Overview

## Table of Contents

- [System Architecture](#system-architecture)
- [Chart of Accounts](#chart-of-accounts)
- [Auto-Mapping Resolver](#auto-mapping-resolver)
- [Transaction Builders](#transaction-builders)
- [Ledger System](#ledger-system)
- [Snapshot System](#snapshot-system)
- [Fluent Configuration](#fluent-configuration)
- [Events](#events)
- [Artisan Commands](#artisan-commands)
- [Data Immutability](#data-immutability)
- [Multi-Tenancy](#multi-tenancy)

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Filament Admin UI                         │
│  AccountResource │ JournalResource │ Reports │ Dashboard Widgets │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Accounting Facade                           │
│  received() │ paid() │ sold() │ purchased() │ transfer() │ ...  │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    AccountingService                             │
│  Transaction builders │ Report generation │ Snapshot management  │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Core Models                                 │
│  Account │ Journal │ JournalEntry │ Budget │ Reconciliation      │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Database Layer                                │
│  accounts │ journals │ journal_entries │ account_snapshots       │
│  budgets │ reconciliations │ bank_statement_lines                │
└─────────────────────────────────────────────────────────────────┘
```

---

## Chart of Accounts

The chart of accounts uses a self-referential hierarchy with up to 4 levels:

### Level 0: Groups

| Group | Code | Description |
|-------|------|-------------|
| Assets | 1000 | Resources owned by the business |
| Liabilities | 2000 | Obligations to creditors |
| Equity | 3000 | Owner's stake in the business |
| Revenue | 4000 | Income from operations |
| Expenses | 5000 | Costs of doing business |

### Level 1: Categories

| Category | Parent | Code |
|----------|--------|------|
| Current Assets | Assets | 1100 |
| Fixed Assets | Assets | 1500 |
| Current Liabilities | Liabilities | 2100 |
| Long-term Liabilities | Liabilities | 2200 |
| Owner Equity | Equity | 3100 |
| Operating Revenue | Revenue | 4100 |
| Non-Operating Revenue | Revenue | 4200 |
| Cost of Goods Sold | Expenses | 5100 |
| Operating Expenses | Expenses | 5200 |
| Non-Operating Expenses | Expenses | 5300 |

### Level 2: Accounts

| Account | Parent | Code | Type |
|---------|--------|------|------|
| Cash on Hand | Current Assets | 1101 | Asset |
| Cash in Bank | Current Assets | 1102 | Asset |
| Accounts Receivable | Current Assets | 1103 | Asset |
| Inventory | Current Assets | 1104 | Asset |
| Accounts Payable | Current Liabilities | 2101 | Liability |
| Sales Revenue | Operating Revenue | 4101 | Revenue |
| Service Revenue | Operating Revenue | 4102 | Revenue |
| Rent Expense | Operating Expenses | 5201 | Expense |
| Salary Expense | Operating Expenses | 5202 | Expense |

### Account Types

```php
enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';
}
```

The account type determines:
- **Normal balance** (debit vs credit)
- **Balance calculation** (positive/negative)
- **Report placement** (Income Statement vs Balance Sheet)

---

## Auto-Mapping Resolver

When you pass an `Accountable` model to a transaction, the resolver automatically creates or finds the appropriate leaf account.

### Resolution Flow

```
1. Transaction receives Accountable model
   └─> $customer (implements Accountable)

2. Resolver calls getAccountKeys()
   └─> Returns AccountSystemKey::AccountsReceivable

3. Resolver finds parent account
   └─> Account where system_key = 'accounts_receivable' AND parent_id is not null

4. Resolver calls getAccountIdentifier()
   └─> ['id' => 42, 'name' => 'Acme Corp']

5. Resolver finds or creates leaf account
   └─> Account where morphable = Customer#42
   └─> Or creates: "Customer 42 - Acme Corp" under AR

6. Transaction uses resolved account
   └─> Journal entry created with leaf account
```

### Dual-Role Entities

For entities that can be both customer and supplier:

```php
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

// The resolver will create TWO accounts:
// - "Partner 1 - Acme" under Accounts Receivable
// - "Partner 1 - Acme" under Accounts Payable
```

---

## Transaction Builders

Each transaction type uses a specialized builder:

| Transaction | Builder Class | Debit Side | Credit Side |
|-------------|---------------|------------|-------------|
| Received | `ReceivedTransaction` | Cash/Bank | Accounts Receivable |
| Paid | `PaidTransaction` | Accounts Payable | Cash/Bank |
| Sold | `SoldTransaction` | Accounts Receivable | Revenue |
| Purchased | `PurchasedTransaction` | Expense/Asset | Accounts Payable |
| Transfer | `TransferTransaction` | Destination Account | Source Account |
| Adjustment | `AdjustmentTransaction` | Explicit Account | Explicit Account |
| Journal | `ManualJournal` | Specified Account | Specified Account |

### Builder Pattern

```php
// All builders follow the same pattern:
Accounting::{type}(amount, description)
    ->from($entity)      // Credit side (who we owe)
    ->to($entity)        // Debit side (who owes us)
    ->toBank()           // Shortcut for debit to bank
    ->fromCash()         // Shortcut for credit from cash
    ->forRevenue($key)   // Specify revenue account
    ->forExpense($key)   // Specify expense account
    ->forAsset($key)     // Specify asset account
    ->tenantId($id)      // Set tenant
    ->commit();          // Create journal + entries
```

### Commit Process

1. Validate amount > 0
2. Resolve entities to accounts (via Accountable interface)
3. Create Journal (status: draft)
4. Create JournalEntry for debit side
5. Create JournalEntry for credit side
6. Validate journal is balanced
7. Fire event (e.g., `JournalReceivedEvent`)
8. Return Journal

---

## Ledger System

The ledger system provides per-account views of journal entries.

### AccountLedger

Shows all entries affecting an account within a period, with a running balance:

```
AccountLedger
- forPeriod($from, $to)    -- Set date range
- forTenant($tenantId)     -- Filter by tenant
- getOpeningBalance()      -- Cumulative before period
- getClosingBalance()      -- Cumulative through period end
- getTotalDebit()          -- Sum of debits in period
- getTotalCredit()         -- Sum of credits in period
- getEntries()             -- Collection of LedgerEntry with running balance
```

### TAccount

Traditional T-account layout separating debits (left) and credits (right):

```
TAccount
- forPeriod($from, $to)    -- Set date range
- forTenant($tenantId)     -- Filter by tenant
- getOpeningBalance()      -- Cumulative before period
- getClosingBalance()      -- Cumulative through period end
- getDebitEntries()        -- Left side
- getCreditEntries()       -- Right side
- getTotalDebit()          -- Sum of debit side
- getTotalCredit()         -- Sum of credit side
```

---

## Snapshot System

Account balances are cached in the `account_snapshots` table for performance.

### Driver Architecture

```
SnapshotManager (like Laravel's CacheManager)
- driver('on_demand')  -> OnDemandDriver
- driver('scheduled')  -> ScheduledDriver
- driver('null')       -> NullDriver
- extend('custom', fn) -> CustomDriver
```

### Drivers

| Driver | Class | Description | Use Case |
|--------|-------|-------------|----------|
| `on_demand` | `OnDemandDriver` | Calculates on first request, stores in DB | Shared hosting, no cron |
| `scheduled` | `ScheduledDriver` | Pre-generates via artisan command | VPS/dedicated servers |
| `null` | `NullDriver` | Always calculates from entries | Development/testing |

### OnDemandDriver Flow

```
1. Request balance for Account X on date Y
   -> Check account_snapshots table

2. If snapshot exists:
   -> Return cached balance

3. If snapshot doesn't exist:
   -> Calculate from journal_entries
   -> Store in account_snapshots
   -> Return calculated balance
```

### ScheduledDriver Flow

```
1. Scheduler runs: php artisan accounting:generate-snapshots
   -> Calculates all account balances for the date
   -> Stores in account_snapshots table

2. Request balance for Account X on date Y
   -> Look up closest pre-generated snapshot
   -> Return cached balance
```

### Custom Drivers

Extend the SnapshotManager with your own driver:

```php
Accounting::snapshot()->extend('redis', function ($balanceService) {
    return new RedisSnapshotDriver($balanceService);
});
```

Custom drivers must implement `SnapshotDriver`:

```php
interface SnapshotDriver
{
    public function getCumulativeBalances(Carbon $asOf, ?int $tenantId = null): Collection;
}
```

---

## Fluent Configuration

The `AccountingConfiguration` class provides a fluent interface for runtime configuration:

```php
Accounting::configure(function ($config) {
    $config->currency('MYR')
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
```

### Schedule Registration

The ServiceProvider checks for a custom schedule callback:

```
ServiceProvider::packageBooted()
  -> Is snapshot.driver == 'scheduled'?
     -> YES: Is there a custom schedule callback?
        -> YES: Call the user's callback with $schedule
        -> NO: Register default schedule (dailyAt from config)
     -> NO: Do nothing
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

All events expose the `Journal` model via `$event->journal`.

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `accounting:install` | Publish config, migrations, run migrations, seed chart of accounts |
| `accounting:generate-snapshots` | Generate balance snapshots for the scheduled driver |

### Generate Snapshots Options

```bash
php artisan accounting:generate-snapshots                    # Today
php artisan accounting:generate-snapshots --date=2024-12-31  # Specific date
php artisan accounting:generate-snapshots --from=2024-01-01 --to=2024-12-31
php artisan accounting:generate-snapshots --tenant=1
```

---

## Data Immutability

When `immutable` is `true` in config:

### Posted Journals

```php
// Cannot modify attributes
$journal->description = 'Changed'; // Throws ImmutableJournalException
$journal->save(); // Throws ImmutableJournalException

// Cannot delete
$journal->delete(); // Throws ImmutableJournalException

// Cannot void without remarks
$journal->void(); // Throws InvalidArgumentException
$journal->void('Valid reason'); // OK
```

### Corrections

To correct a posted journal:

1. **Void the original** (with remarks)
2. **Create a new journal** with correct data
3. **Or create an adjustment entry** to offset the error

---

## Multi-Tenancy

All accounting tables include a `tenant_id` column for SaaS isolation.

### Singleton Tenant Context

The package uses a singleton-based tenant context. Set the current tenant once (e.g., in middleware), and all transactions automatically use it:

```php
use AloongJerr\Accounting\Facades\Accounting;

// Set current tenant (e.g., in middleware)
Accounting::setTenant($user->tenant_id);

// Get current tenant
$tenantId = Accounting::getTenant();

// Transactions auto-use the singleton's tenant
Accounting::received(50000, 'Payment')
    ->from($customer)
    ->toBank()
    ->commit(); // Uses tenant from singleton
```

### Explicit Tenant Override

Override the singleton tenant when needed:

```php
// Explicit tenant for a specific transaction
Accounting::received(50000, 'Platform fee')
    ->forTenant(null)  // Platform-level (no tenant)
    ->commit();

// Or for a specific tenant
Accounting::received(50000, 'Tenant payment')
    ->forTenant($tenantId)
    ->commit();
```

### Tenant-Scoped Reports

```php
// Reports use singleton tenant by default
Accounting::trialBalance()
    ->asOf('2024-12-31')
    ->get();

// Or specify tenant explicitly
Accounting::trialBalance()
    ->forTenant($tenantId)
    ->asOf('2024-12-31')
    ->get();

// Models have tenant scope
Journal::forTenant($tenantId)->get();
```

### Tenant Data Structure

Tenants can be any model in your application. The `tenant_id` column stores the ID:
- `null` - Platform-level (no tenant)
- `1`, `2`, `3`... - Specific tenant IDs

---

## Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| `accounts` | Chart of accounts (self-referential hierarchy) |
| `journals` | Transaction headers |
| `journal_entries` | Transaction lines (debit/credit) |
| `account_snapshots` | Cached account balances |
| `budgets` | Budget allocations per account/period |
| `reconciliations` | Bank reconciliation sessions |
| `bank_statement_lines` | Bank statement entries |

### Key Relationships

```
Account
- parent_id -> Account (self-referential)
- children -> Account[]
- journalEntries -> JournalEntry[]
- morphable -> Accountable model (polymorphic)
- snapshots -> AccountSnapshot[]

Journal
- entries -> JournalEntry[]
- tenant -> Tenant

JournalEntry
- account -> Account
- journal -> Journal
- tenant -> Tenant
```
