# Changelog

All notable changes to `accounting` will be documented in this file.

## 1.5.0 - 2025-08-23

### Bank Reconciliation
- Add `Reconciliation` model with draft/completed workflow
- Add `BankStatementLine` model with match/unmatch functionality
- Add `ReconciliationReport` comparing bank vs system entries
- Add `ReconciliationRow` value object for report rows
- Add `create_reconciliation_tables` migration
- Add `reconciliationReport()` through AccountingService and Facade
- Add bilingual translations (en/ms) for bank reconciliation
- 35 new tests (12 BankStatementLine + 13 Reconciliation + 10 ReconciliationReport)

## 1.4.0 - 2025-08-23

### Budget vs Actual
- Add `Budget` model with period scopes and tenant support
- Add `BudgetReport` with variance analysis (budgeted vs actual + %)
- Add `BudgetRow` value object
- Add `create_budgets_table` migration
- Add `budgetReport()` through AccountingService and Facade
- Add bilingual translations (en/ms) for budget report
- 19 new tests (10 BudgetModel + 9 BudgetReport)

## 1.3.0 - 2025-08-23

### AR/AP Aging Report
- Add `AgingReport` with per-account aggregation and age buckets (current, 31-60, 61-90, over 90)
- Add `AgingRow` value object
- Add `aging()` through AccountingService and Facade
- Add bilingual translations (en/ms) for aging report
- 12 new tests

## 1.2.0 - 2025-08-23

### HasAccountMapping Trait
- Add `HasAccountMapping` trait for simplified `Accountable` interface implementation
- Models only need to define `getAccountSystemKeys()` to enable auto-mapping
- Customizable display name via `getAccountName()` override
- 9 new tests

## 1.1.0 - 2025-08-23

### Transfer Transaction
- Add `TransferTransaction` with fluent API (`fromCash`/`fromBank`/`from`/`toCash`/`toBank`/`to`/`fromSystemKey`/`toSystemKey`)
- Add `JournalTransferredEvent`
- Pass `$data` to `Accountable` interface methods for dynamic account resolution
- Add bilingual translations (en/ms) for transfer transaction
- 14 new tests

## 1.0.0 - 2025-08-20

### Initial Release
- Double-entry bookkeeping with balanced journal entries
- Fluent transaction API: `received()`, `paid()`, `sold()`, `purchased()`, `journal()`
- `Accountable` interface for auto-mapping customer/supplier accounts
- Hierarchical chart of accounts with 50+ pre-seeded accounts
- Immutable journal entries (posted journals cannot be modified)
- Journal workflow: Draft → Posted → Void
- Financial reports: Trial Balance, Income Statement, Balance Sheet
- Snapshot system for optimized balance calculations (on_demand, scheduled, null drivers)
- Multi-tenant support with tenant isolation
- Filament v5 admin interface with resources, pages, and widgets
- Bilingual translations (English/Malay)
- Configurable database connection
- Configurable fiscal year
- 244 tests passing
