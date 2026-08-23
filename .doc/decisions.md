# Project Decisions Log

> This file records all decisions made throughout the development of `aloongjerr/accounting`.

---

## Format

Each entry follows this format:

### [DATE] Decision Title
- **Context:** Why this decision was made
- **Decision:** What was decided
- **Alternatives considered:** Other options discussed (if any)
- **Outcome:** Result or next steps

---

## Decisions

### [2026-08-23] Fluent API Design Pattern for Journal Entries
- **Context:** User wants zero accounting knowledge required. System must auto-map transactions to correct debit/credit entries.
- **Decision:** Use a **fluent builder pattern** with chainable methods. Entry point is `Accounting::journal()`, followed by transaction type (`paid`, `received`, `sold`, `purchased`, `adjustment`), then flow methods (`from`, `to`, `viaCash`, `viaBank`, etc.), terminated by `commit()`.
- **Alternatives considered:** Traditional service-per-operation (rejected — too verbose), raw debit/credit API (rejected — requires accounting knowledge)
- **Outcome:** Design the `JournalService` class hierarchy with auto-mapping logic

### [2026-08-23] Single Entity, Future-Ready for Multi-Company/Currency
- **Context:** Current scope is single company, single currency. But must be extensible.
- **Decision:** All models and services will include `company_id` and `currency` as reserved fields (nullable for now). Services will accept optional company/currency context for future use.
- **Outcome:** Schema and service design must account for multi-entity from day one

### [2026-08-23] Fiscal Year Configuration
- **Context:** Users need to define their own fiscal year period.
- **Decision:** Fiscal year start/end month will be configurable via `config/accounting.php`
- **Outcome:** Add fiscal year config to the config file

### [2026-08-23] Account Hierarchy — Individual Accounts Link to Sub-Account Types, Not Directly to Chart of Accounts
- **Context:** User correctly identified that `individual_accounts` should not FK directly to `chart_of_accounts`. There must be an intermediate layer.
- **Decision:** Use a self-referential `accounts` table with `type` column (group/category/account) and `parent_id` for unlimited depth hierarchy. One table instead of 3 separate tables.
- **Alternatives considered:** 3 separate tables (`chart_of_accounts` + `sub_account_types` + `individual_accounts`) — rejected as too rigid
- **Outcome:** Design accounts table schema with self-referential hierarchy

### [2026-08-23] Enum-Based System Keys with Layered Resolver
- **Context:** Need a type-safe, extensible way to identify system accounts and resolve entity roles in transactions.
- **Decision:**
  - Use PHP BackedEnum (`AccountSystemKey`) for system account keys (default + user custom via config)
  - `accounts` table gets nullable `system_key` column for system-recognized accounts
  - 3-layer separation: Enum = identity + parent, Config = hierarchy mapping, Resolver = transaction context role resolution
  - `Accountable` interface returns `AccountSystemKey|array` (identity only, no role logic)
  - Resolver determines role from transaction type + direction, matches to correct enum key
- **Alternatives considered:** (a) `getAccountRole()` on enum — rejected, violates single responsibility; (b) Account name/code lookup — rejected, fragile
- **Outcome:** Locked. Proceed to full schema design.

### [2026-08-23] Coding Standards & Conventions
- **Context:** Establish consistent coding patterns across the package.
- **Decisions:**
  1. **Enums for all fixed values** — Every fixed/constant value used anywhere in the code must be an enum. No magic strings or hardcoded constants.
  2. **Traits for shared processes** — Any method that performs the same process across multiple classes must be extracted into a trait. No duplication.
  3. **Filament HasLabel for display enums** — Any enum that will be displayed in UI must implement Filament's `HasLabel` contract (with `getLabel()` method).
  4. **Computed values as enum methods** — When a computed value depends on an enum value, put the computation logic as a method on the enum itself, accepting parameters. Keep the logic with the data it depends on.
- **Outcome:** These conventions apply to all phases of development

### [2026-08-23] Currency Handling Strategy
- **Context:** Need to support currency now (single) and multi-currency later.
- **Decision:** Default currency from `config/accounting.php`. When `company_id` is attached to a record, fetch currency from `companies` table. Fallback to config default.
- **Outcome:** Add `currency` column to relevant tables, implement currency resolution logic in a trait

### [2026-08-23] Table Naming — journals + journal_entries
- **Context:** User prefers specific table names different from initial proposal.
- **Decision:**
  - `journals` = header table (id, date, description, reference_type, reference_id, status, company_id, comments(json), timestamps)
  - `journal_entries` = line items table (id, journal_id, account_id, debit, credit, description, timestamps)
- **Alternatives considered:** `journal_entries` + `journal_entry_lines` — rejected, user preference
- **Outcome:** Use `journals` + `journal_entries` naming throughout

### [2026-08-23] Parent Hierarchy in Enum — No Config parent_map
- **Context:** The `parent_map` in config duplicated what `parentKey()` already does in the enum.
- **Decision:** Remove `parent_map` from config entirely. Parent hierarchy lives solely in the enum via `parentKey()` method. Custom enums must implement `HasAccountIdentity` contract. The 3-layer model is now: Enum (identity + hierarchy), Config (register enums only), Resolver (transaction context).
- **Outcome:** Created `HasAccountIdentity` contract, updated `AccountSystemKey` to implement it, removed `parent_map` from config

### [2026-08-23] Enum getCode() Method — No Separate Generator Classes
- **Context:** Initial design used separate `GeneratesAccountCode` interface + `DefaultCodeGenerator` class + config-based generator resolution. Over-engineered.
- **Decision:** Replace with `getCode()` method directly on the `HasAccountIdentity` contract. Each enum implements its own `getCode(): string` with `match($this)`. Consistent with `parentKey()` and `getLabel()` pattern — everything self-contained in the enum.
- **Outcome:** Removed `GeneratesAccountCode`, `DefaultCodeGenerator`, `code_generators` config. Updated seeder to call `$key->getCode()` directly. Renamed contract to `HasAccountIdentity` to better reflect its purpose.

### [2026-08-23] Journal Comments via Fluent Builder Only
- **Context:** The `journals` table has a `comments` (json) column. Considered whether entities should auto-provide comments via interface.
- **Decision:** Comments are added via `comment()` method on the fluent builder only. No entity interface needed. Filament forms (Phase 5) will map form data to `comment()` calls directly.
- **Outcome:** Deferred entity-level comment interface. Builder `comment()` method is sufficient for now.

### [2026-08-23] Test Migration Stub Handling
- **Context:** Migration files are `.stub` but Laravel's migrator only scans `*.php`. `loadMigrationsFrom` doesn't detect stubs.
- **Decision:** TestCase copies `.stub` files to `tests/database/migrations/` as `.php` in `setUp()`, cleans up in `tearDown()`. Directory added to `.gitignore`.
- **Outcome:** Tests can run migrations from stubs without maintaining duplicate migration files.

### [2026-08-23] Phase 2 — Transaction Events on Commit
- **Context:** Every `commit()` call should fire a typed Laravel event so users can create listeners for post-transaction actions.
- **Decision:** Each transaction type fires its own event class (e.g., `JournalReceivedEvent`, `JournalPaidEvent`). Events carry journal model, amount, from/to entities.
- **Outcome:** Phase 2 will include event classes in `src/Events/`. Commit logic dispatches the appropriate event after successful journal creation.

### [2026-08-23] Phase 2 — pipeThrough() Middleware Pipeline
- **Context:** Need a modular way for users to run custom logic before commit (validation, transformation, budget checks). Events handle "after commit" but "before commit" needed a pattern.
- **Decision:** Add `pipeThrough(array $pipes)` method on the fluent builder. Each pipe implements `AccountingPipe` contract with `handle($transaction, $next)`. Uses Laravel's Pipeline pattern. Executes in sequence before `commit()` persists.
- **Outcome:** Phase 2 will include `AccountingPipe` contract in `src/Contracts/`, pipeline execution in builder's `commit()` method. No beforeCommit/afterCommit methods needed — pipes handle before, events handle after.

### [2026-08-23] Hybrid Transaction API — Named Types + Generic journal()
- **Context:** 5 named types (received, paid, sold, purchased, adjustment) don't cover all scenarios (returns, refunds, contra, transfers, depreciation). Adding a method per scenario is not scalable.
- **Decision:** Hybrid approach — keep named types as convenience wrappers + add `journal()` as generic escape hatch. `journal()` allows explicit `debit($account, $amount)` / `credit($account, $amount)` chaining for any custom combination.
- **Outcome:** Phase 3 will include `ManualJournal` builder for custom entries alongside named transaction types. Covers 90% with named types, remaining 10% with `journal()`.

### [2026-08-23] Monetary Amounts Stored as Integer Cents
- **Context:** Floating point arithmetic causes precision issues in financial calculations. All monetary values must be stored as integers in the smallest currency unit (cents/sen).
- **Decision:** DB columns use `unsignedBigInteger` for debit/credit. Model casts use `integer`. All amounts passed via API are in cents (e.g., RM5.40 = 540). Display formatting to decimal happens at presentation layer only.
- **Outcome:** No floating point in storage or calculations. RM5.40 → stored as `540` → displayed as `RM5.40`.
