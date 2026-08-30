<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\BankStatementLine;
use AloongJerr\Accounting\Models\Reconciliation;
use AloongJerr\Accounting\Reports\ReconciliationReport;
use AloongJerr\Accounting\ValueObjects\ReconciliationRow;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);

    $this->bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();
    $this->today = Carbon::today();
});

it('returns empty report when reconciliation not found', function () {
    $report = new ReconciliationReport(999);
    $data = $report->get();

    expect($data)->toBeEmpty();
});

it('returns empty rows when no statement lines exist', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1000000,
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $data = $report->get();

    expect($data)->toBeEmpty();
});

it('shows matched items', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    // Create a bank line and match it
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(5),
        'description' => 'Customer payment',
        'amount' => 500000,
        'reference' => 'BANK001',
        'type' => 'credit',
        'journal_entry_id' => 1,
        'is_matched' => true,
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $data = $report->get();

    expect($data)->toHaveCount(1);

    $row = $data->first();
    expect($row)->toBeInstanceOf(ReconciliationRow::class);
    expect($row->type)->toBe('matched');
    expect($row->statementLineId)->toBe($line->id);
    expect($row->journalEntryId)->toBe(1);
    expect($row->description)->toBe('Customer payment');
    expect($row->amount)->toBe(500000);
});

it('shows unmatched bank items', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1200000,
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(10),
        'description' => 'Bank fee',
        'amount' => -200000,
        'type' => 'debit',
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $data = $report->get();

    expect($data)->toHaveCount(1);

    $row = $data->first();
    expect($row->type)->toBe('unmatched_bank');
    expect($row->journalEntryId)->toBeNull();
    expect($row->description)->toBe('Bank fee');
    expect($row->amount)->toBe(200000);
});

it('shows unmatched system entries', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    // Create a system transaction affecting the bank account
    $journal = Accounting::received(500000, 'Customer payment received')
        ->toBank()
        ->fromAccount(Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first())
        ->onDate($this->today->copy()->startOfMonth()->addDays(5))
        ->commit();
    $journal->post();

    $report = new ReconciliationReport($reconciliation->id);
    $data = $report->get();

    // Should have unmatched system entries (the bank entry from the received transaction)
    $systemRows = $data->filter(fn ($r) => $r->type === 'unmatched_system');
    expect($systemRows->count())->toBeGreaterThan(0);
});

it('creates via facade', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1000000,
    ]);

    $report = Accounting::reconciliationReport($reconciliation->id);

    expect($report)->toBeInstanceOf(ReconciliationReport::class);
});

it('summary returns correct structure', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1000000,
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $summary = $report->summary();

    expect($summary->reconciliation_id)->toBe($reconciliation->id);
    expect($summary->account_id)->toBe($this->bankAccount->id);
    expect($summary->bank_statement_balance)->toBeInt();
    expect($summary->system_balance)->toBeInt();
    expect($summary->matched_total)->toBeInt();
    expect($summary->unmatched_bank_total)->toBeInt();
    expect($summary->unmatched_system_total)->toBeInt();
    expect($summary->difference)->toBeInt();
    expect($summary->is_balanced)->toBeBool();
});

it('summary returns zero values when reconciliation not found', function () {
    $report = new ReconciliationReport(999);
    $summary = $report->summary();

    expect($summary->reconciliation_id)->toBe(0);
    expect($summary->account_id)->toBe(0);
    expect($summary->bank_statement_balance)->toBe(0);
    expect($summary->difference)->toBe(0);
    expect($summary->is_balanced)->toBeTrue();
});

it('summary calculates bank statement balance correctly', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000, // $10,000
        'closing_balance' => 1300000,
    ]);

    // Add bank credits
    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(5),
        'description' => 'Customer payment',
        'amount' => 500000, // $5,000 credit
        'type' => 'credit',
    ]);

    // Add bank debits
    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(10),
        'description' => 'Bank fee',
        'amount' => -200000, // $2,000 debit
        'type' => 'debit',
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $summary = $report->summary();

    // Bank balance = opening (1000000) + credits (500000) - debits (200000) = 1300000
    expect($summary->bank_statement_balance)->toBe(1300000);
});

it('summary tracks matched and unmatched totals', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    // Matched line
    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(5),
        'description' => 'Matched payment',
        'amount' => 300000,
        'type' => 'credit',
        'journal_entry_id' => 1,
        'is_matched' => true,
    ]);

    // Unmatched bank line
    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(10),
        'description' => 'Unmatched bank fee',
        'amount' => -100000,
        'type' => 'debit',
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $summary = $report->summary();

    expect($summary->matched_total)->toBe(300000);
    expect($summary->unmatched_bank_total)->toBe(100000);
});

it('rows are sorted by date', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => $this->today->copy()->startOfMonth(),
        'end_date' => $this->today->copy()->endOfMonth(),
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    // Add lines in reverse date order
    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(20),
        'description' => 'Later transaction',
        'amount' => 200000,
        'type' => 'credit',
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => $this->today->copy()->startOfMonth()->addDays(5),
        'description' => 'Earlier transaction',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    $report = new ReconciliationReport($reconciliation->id);
    $data = $report->get();

    expect($data)->toHaveCount(2);
    expect($data->first()->date)->toBeLessThanOrEqual($data->last()->date);
});
