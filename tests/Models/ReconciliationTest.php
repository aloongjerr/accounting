<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\BankStatementLine;
use AloongJerr\Accounting\Models\Reconciliation;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);

    $this->bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();
});

it('creates a reconciliation with correct attributes', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
        'status' => 'draft',
    ]);

    expect($reconciliation->account_id)->toBe($this->bankAccount->id);
    expect($reconciliation->opening_balance)->toBe(1000000);
    expect($reconciliation->closing_balance)->toBe(1500000);
    expect($reconciliation->status)->toBe('draft');
});

it('belongs to an account', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    expect($reconciliation->account)->toBeInstanceOf(Account::class);
    expect($reconciliation->account->id)->toBe($this->bankAccount->id);
});

it('has many statement lines', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => '2024-01-10',
        'description' => 'Line 1',
        'amount' => 100000,
        'type' => 'credit',
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Line 2',
        'amount' => 200000,
        'type' => 'debit',
    ]);

    expect($reconciliation->statementLines)->toHaveCount(2);
});

it('casts dates to Carbon instances', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    $reconciliation = $reconciliation->fresh();

    expect($reconciliation->start_date)->toBeInstanceOf(Carbon::class);
    expect($reconciliation->end_date)->toBeInstanceOf(Carbon::class);
});

it('casts balances to integer', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    $reconciliation = $reconciliation->fresh();

    expect($reconciliation->opening_balance)->toBeInt();
    expect($reconciliation->closing_balance)->toBeInt();
});

it('scopes to account', function () {
    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    $reconciliations = Reconciliation::query()
        ->forAccount($this->bankAccount->id)
        ->get();

    expect($reconciliations)->toHaveCount(1);
});

it('scopes to period', function () {
    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-28',
        'opening_balance' => 1500000,
        'closing_balance' => 1800000,
    ]);

    $reconciliations = Reconciliation::query()
        ->forPeriod('2024-01-01', '2024-01-31')
        ->get();

    expect($reconciliations)->toHaveCount(1);
});

it('scopes to tenant', function () {
    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
        'tenant_id' => 1,
    ]);

    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-28',
        'opening_balance' => 1500000,
        'closing_balance' => 1800000,
        'tenant_id' => 2,
    ]);

    $reconciliations = Reconciliation::query()->forTenant(1)->get();

    expect($reconciliations)->toHaveCount(1);
    expect($reconciliations->first()->tenant_id)->toBe(1);
});

it('scopes to draft and completed', function () {
    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
        'status' => 'draft',
    ]);

    Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-28',
        'opening_balance' => 1500000,
        'closing_balance' => 1800000,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    expect(Reconciliation::query()->draft()->count())->toBe(1);
    expect(Reconciliation::query()->completed()->count())->toBe(1);
});

it('marks as completed', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
        'status' => 'draft',
    ]);

    $reconciliation->complete();

    $reconciliation = $reconciliation->fresh();

    expect($reconciliation->status)->toBe('completed');
    expect($reconciliation->completed_at)->not->toBeNull();
});

it('detects overlapping periods', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    expect($reconciliation->overlaps(Carbon::parse('2024-01-15'), Carbon::parse('2024-01-20')))->toBeTrue();
    expect($reconciliation->overlaps(Carbon::parse('2024-02-01'), Carbon::parse('2024-02-28')))->toBeFalse();
});

it('counts matched and unmatched lines', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => '2024-01-10',
        'description' => 'Matched',
        'amount' => 100000,
        'type' => 'credit',
        'journal_entry_id' => 1,
        'is_matched' => true,
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => '2024-01-15',
        'description' => 'Unmatched 1',
        'amount' => 200000,
        'type' => 'debit',
    ]);

    BankStatementLine::query()->create([
        'reconciliation_id' => $reconciliation->id,
        'transaction_date' => '2024-01-20',
        'description' => 'Unmatched 2',
        'amount' => 150000,
        'type' => 'credit',
    ]);

    expect($reconciliation->matchedCount())->toBe(1);
    expect($reconciliation->unmatchedCount())->toBe(2);
});

it('defaults status to draft', function () {
    $reconciliation = Reconciliation::query()->create([
        'account_id' => $this->bankAccount->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'opening_balance' => 1000000,
        'closing_balance' => 1500000,
    ]);

    expect($reconciliation->status)->toBe('draft');
});
