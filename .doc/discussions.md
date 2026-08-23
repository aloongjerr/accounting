# Discussion Notes

> This file captures key discussion points, ideas, and directions throughout the development.

---

## Format

### [DATE] Topic
- **Points raised:**
- **Conclusions:**
- **Action items:**

---

## Notes

### [2026-08-23] Core Service Class Architecture
- **Points raised:**
  - User wants a service class layer to handle core accounting operations
  - Required capabilities:
    1. Journal Entry
    2. Ledger
    3. Trial Balance
    4. Income Statement (pending confirmation if necessary)
    5. Balance Sheet
    6. Other related accounting features
- **Conclusions:**
  1. Income Statement confirmed as part of the project scope
  2. Single company & single currency for now, but all decisions must be **future-ready** for multi-company/currency
  3. Fiscal year configurable by user via config file
  4. Architecture: **Fluent API with auto-mapping**
     - `Accounting::journal()` → returns `JournalService`
     - Transaction type methods: `->paid()`, `->received()`, `->sold()`, `->purchased()`, `->adjustment()`
     - Chainable flow methods: `->from()`, `->to()`, `->fromCash()`, `->toCash()`, `->viaBank($model)`, `->comments()`, etc.
     - `->commit()` finalizes and auto-maps to correct debit/credit accounts
  5. **Key design principle:** User does NOT need accounting knowledge. Every `commit()` auto-maps transaction data to correct accounts, sub-accounts, debit/credit based on transaction type.
- **Action items:** Design fluent API class hierarchy, define account resolution strategy, create data models

### [2026-08-23] Accounting Domain Knowledge Clarification
- **Points raised:**
  - User needs full list of real-world transaction types
  - Clarification on account types and sub-account types (fixed vs flexible)
  - How to auto-map individual accounts to correct sub-accounts
  - Morphable model linking (model_type, model_id) for dynamic accounts
  - Default/fixed accounts vs dynamic accounts (e.g., Cash = fixed, Bank = multiple)
- **Conclusions:** Resolved — dual-role entities get separate account records (see next discussion)
- **Action items:** Finalize account type list, design Chart of Accounts hierarchy, design Accountable interface

### [2026-08-23] Dual-Role Entity Problem
- **Points raised:**
  - Same entity (e.g., Kazim Enterprise) can be both a vendor AND a customer
  - When you buy PC from them on credit → they are a liability (AP)
  - When they buy furniture from you on credit → they are an asset (AR)
  - Can one `individual_account` record handle both, or need two separate records?
- **Conclusions:** Resolved — one entity has MULTIPLE individual accounts, resolved by transaction context
- **Action items:** Redesign Accountable interface to be identity-only, let transaction context determine account role

### [2026-08-23] Resolver System for Auto-Mapping
- **Points raised:**
  - Package needs a resolver to handle mapping models to correct accounts
  - Resolver must understand transaction context + direction to determine account role
  - Resolver must handle both dynamic accounts (vendors, customers, banks) and default accounts (cash, inventory)
  - Resolver must auto-create accounts when they don't exist yet
- **Conclusions:** Resolved — 3-layer architecture (Enum → Config → Resolver)
- **Action items:** Design AccountResolver class and mapping rules

### [2026-08-23] Resolver Design — Enum-Based System Keys & Accountable Interface
- **Points raised:**
  - User proposes BackedEnum for system keys (default + custom via config)
  - User proposes Accountable returns BackedEnum|array with `getAccountRole($transactionType)` method
  - Need to analyze if enum should contain role resolution logic or not
- **Conclusions:**
  - Enum-based system keys accepted
  - 3-layer resolver architecture accepted (Enum → Config → Resolver)
  - `Accountable` returns `AccountSystemKey|array` for identity, resolver handles role mapping
- **Action items:** Finalize resolver architecture

### [2026-08-23] Development Phases Planning
- **Points raised:**
  - Need to define development phases before coding
  - Must be logical, each phase building on the previous
- **Conclusions:**
  - 6-phase plan accepted (Foundation → Resolver → Transactions → Reports → Filament UI → Testing)
  - Each phase builds on the previous, Phase 6 can overlap with 3-5
- **Action items:** Begin Phase 1 implementation

### [2026-08-23] Coding Standards & Conventions
- **Points raised:**
  - All fixed values must use enums (no magic strings)
  - Shared process methods across classes must be extracted into traits
  - Display enums must implement Filament HasLabel contract
  - Computed values using enum data should be methods on the enum itself
- **Conclusions:** All 4 conventions accepted and locked for all phases
- **Action items:** Apply conventions throughout all development
