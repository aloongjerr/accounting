# Architecture Overview

## Table of Contents

- [System Architecture](#system-architecture)
- [Chart of Accounts](#chart-of-accounts)
- [Auto-Mapping Resolver](#auto-mapping-resolver)
- [Transaction Builders](#transaction-builders)
- [Snapshot System](#snapshot-system)
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
| Journal | `ManualTransaction` | Specified Account | Specified Account |

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

## Snapshot System

Account balances are cached in the `account_snapshots` table for performance.

### Drivers

| Driver | Description | Use Case |
|--------|-------------|----------|
| `on_demand` | Calculates on first request, stores for subsequent reads | Shared hosting, no cron |
| `scheduled` | Pre-generates via artisan command + scheduler | VPS/dedicated servers |
| `null` | Always calculates from entries | Development/testing |

### How It Works

```
1. Request balance for Account X on date Y
   └─> Check account_snapshots table

2. If snapshot exists:
   └─> Return cached balance

3. If snapshot doesn't exist:
   └─> Calculate from journal_entries
   └─> Store in account_snapshots
   └─> Return calculated balance
```

### Scheduled Snapshots

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('accounting:generate-snapshots')->dailyAt('02:00');
}
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

### Tenant Isolation

```php
// Transactions are scoped by tenant
Accounting::received(50000, 'Payment')
    ->from($customer)
    ->toBank()
    ->tenantId($tenantId)
    ->commit();

// Reports filter by tenant
Accounting::trialBalance()
    ->forTenant($tenantId)
    ->asOf('2024-12-31')
    ->get();

// Models have tenant scope
Journal::forTenant($tenantId)->get();
```

### Tenant Configuration

Tenants are stored in the `tenants` table with:
- `id` - Unique tenant identifier
- `name` - Tenant display name
- `currency` - Tenant-specific currency (overrides default)

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
├── parent_id → Account (self-referential)
├── children → Account[]
├── journalEntries → JournalEntry[]
├── morphable → Accountable model (polymorphic)
└── snapshots → AccountSnapshot[]

Journal
├── entries → JournalEntry[]
└── tenant → Tenant

JournalEntry
├── account → Account
├── journal → Journal
└── tenant → Tenant
```
