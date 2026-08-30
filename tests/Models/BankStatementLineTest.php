<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\BankStatementLine;
use AloongJerr\Accounting\Models\Reconciliation;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);

    $this->bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    $this->reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
        'status' => 'draft',
    ]);
});

it('creates a bank statement line with correct attributes', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Customer payment received',
        'amount' => 500000,
        'reference' => 'REF001',
        'type' => 'credit',
    ]);

    expect($line->reconciliation_id)->toBe($this->reconciliation->id);
    expect($line->amount)->toBe(500000);
    expect($line->type)->toBe('credit');
    expect($line->is_matched)->toBeFalse();
    expect($line->journal_entry_id)->toBeNull();
});

it('belongs to a reconciliation', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 100000,
        'type' => 'debit',
    ]);

    expect($line->reconciliation)->toBeInstanceOf(Reconciliation::class);
    expect($line->reconciliation->id)->toBe($this->reconciliation->id);
});

it('casts transaction_date to Carbon', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    $line = $line->fresh();

    expect($line->transaction_date)->toBeInstanceOf(Carbon::class);
});

it('casts amount to integer', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 250000,
        'type' => 'credit',
    ]);

    $line = $line->fresh();

    expect($line->amount)->toBeInt();
    expect($line->amount)->toBe(250000);
});

it('casts is_matched to boolean', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    $line = $line->fresh();

    expect($line->is_matched)->toBeBool();
    expect($line->is_matched)->toBeFalse();
});

it('scopes to reconciliation', function () {
    BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Line 1',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    // Create another reconciliation
    $reconciliation2 = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-28',
        'opening_balance' => 1500000,
        'closing_balance' => 1800000,
        'status' => 'draft',
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation2->id,
        'transaction_date' => '2024-02-10',
        'description' => 'Line 2',
        'amount' => 300000,
        'type' => 'debit',
    ]);

    $lines = BankStatementLine::query()
        ->forReconciliation($this->reconciliation->id)
        ->get();

    expect($lines)->toHaveCount(1);
    expect($lines->first()->description)->toBe('Line 1');
});

it('scopes to matched and unmatched', function () {
    BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-10',
        'description' => 'Matched line',
        'amount' => 100000,
        'type' => 'credit',
        'journal_entry_id' => 1,
        'is_matched' => true,
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Unmatched line',
        'amount' => 200000,
        'type' => 'debit',
    ]);

    $matched = BankStatementLine::query()->matched()->get();
    $unmatched = BankStatementLine::query()->unmatched()->get();

    expect($matched)->toHaveCount(1);
    expect($matched->first()->description)->toBe('Matched line');
    expect($unmatched)->toHaveCount(1);
    expect($unmatched->first()->description)->toBe('Unmatched line');
});

it('scopes to debits and credits', function () {
    BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-10',
        'description' => 'Credit line',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Debit line',
        'amount' => -50000,
        'type' => 'debit',
    ]);

    expect(BankStatementLine::query()->credits()->count())->toBe(1);
    expect(BankStatementLine::query()->debits()->count())->toBe(1);
});

it('matches to a journal entry', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    $line->matchTo(42);

    $line = $line->fresh();

    expect($line->is_matched)->toBeTrue();
    expect($line->journal_entry_id)->toBe(42);
});

it('unmatches from a journal entry', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 100000,
        'type' => 'credit',
        'journal_entry_id' => 42,
        'is_matched' => true,
    ]);

    $line->unmatch();

    $line = $line->fresh();

    expect($line->is_matched)->toBeFalse();
    expect($line->journal_entry_id)->toBeNull();
});

it('returns absolute amount', function () {
    $creditLine = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Credit',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    $debitLine = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-16',
        'description' => 'Debit',
        'amount' => -50000,
        'type' => 'debit',
    ]);

    expect($creditLine->absoluteAmount())->toBe(100000);
    expect($debitLine->absoluteAmount())->toBe(50000);
});

it('allows null reference', function () {
    $line = BankStatementLine::query()->create([
        'reconciliation_id' => $this->reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Test line',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    expect($line->reference)->toBeNull();
});
