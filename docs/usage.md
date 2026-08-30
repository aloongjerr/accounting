# Usage Guide

## Table of Contents

- [Transactions](#transactions)
  - [Receiving Money](#receiving-money)
  - [Paying Money](#paying-money)
  - [Recording Sales](#recording-sales)
  - [Recording Purchases](#recording-purchases)
  - [Transfers](#transfers)
  - [Manual Journal Entries](#manual-journal-entries)
- [Account Mapping](#account-mapping)
  - [Accountable Interface](#accountable-interface)
  - [HasAccountMapping Trait](#hasaccountmapping-trait)
  - [Custom Account Names](#custom-account-names)
- [Journals](#journals)
  - [Journal Lifecycle](#journal-lifecycle)
  - [Voiding Journals](#voiding-journals)
- [Reports](#reports)
  - [Trial Balance](#trial-balance)
  - [Income Statement](#income-statement)
  - [Balance Sheet](#balance-sheet)
  - [AR/AP Aging Report](#arap-aging-report)
  - [Budget vs Actual](#budget-vs-actual)
  - [Bank Reconciliation](#bank-reconciliation)
- [Multi-Tenancy](#multi-tenancy)

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
