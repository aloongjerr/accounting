<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Models\JournalEntry;

beforeEach(function () {
    $this->debitAccount = Account::query()->create([
        'name' => 'Cash on Hand',
        'code' => '1101',
        'type' => AccountType::Account,
        'system_key' => AccountSystemKey::CashOnHand,
        'is_active' => true,
    ]);

    $this->creditAccount = Account::query()->create([
        'name' => 'Sales Revenue',
        'code' => '4101',
        'type' => AccountType::Account,
        'system_key' => AccountSystemKey::SalesRevenue,
        'is_active' => true,
    ]);
});

it('has correct fillable attributes', function () {
    $journal = Journal::query()->create([
        'date' => '2026-01-15',
        'description' => 'Test journal entry',
        'status' => JournalStatus::Draft,
    ]);

    expect($journal->date->format('Y-m-d'))->toBe('2026-01-15');
    expect($journal->description)->toBe('Test journal entry');
    expect($journal->status)->toBe(JournalStatus::Draft);
});

it('casts date to Carbon instance', function () {
    $journal = Journal::query()->create([
        'date' => '2026-06-01',
        'description' => 'Test',
        'status' => JournalStatus::Draft,
    ]);

    expect($journal->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('casts status to JournalStatus enum', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Draft,
    ]);

    $fresh = Journal::find($journal->getKey());
    expect($fresh->status)->toBeInstanceOf(JournalStatus::class);
    expect($fresh->status)->toBe(JournalStatus::Draft);
});

it('casts comments to array', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Draft,
        'comments' => ['Comment 1', 'Comment 2'],
    ]);

    $fresh = Journal::find($journal->getKey());
    expect($fresh->comments)->toBeArray();
    expect($fresh->comments)->toHaveCount(2);
    expect($fresh->comments[0])->toBe('Comment 1');
});

it('defaults status to draft', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
    ]);

    expect($journal->fresh()->status)->toBe(JournalStatus::Draft);
});

it('has entries relationship', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->debitAccount->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    expect($journal->entries)->toHaveCount(1);
});

it('has morphable reference relationship', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Draft,
    ]);

    expect($journal->reference())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('checks if journal is balanced', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Balanced entry',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->debitAccount->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->creditAccount->getKey(),
        'debit' => 0,
        'credit' => 100000,
    ]);

    expect($journal->isBalanced())->toBeTrue();
});

it('detects unbalanced journal', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Unbalanced entry',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->debitAccount->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->creditAccount->getKey(),
        'debit' => 0,
        'credit' => 50000,
    ]);

    expect($journal->isBalanced())->toBeFalse();
});

it('calculates total debit and credit', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->debitAccount->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->creditAccount->getKey(),
        'debit' => 0,
        'credit' => 100000,
    ]);

    expect($journal->totalDebit())->toBe(100000);
    expect($journal->totalCredit())->toBe(100000);
});

it('posts balanced journal successfully', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Balanced',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->debitAccount->getKey(),
        'debit' => 50000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->creditAccount->getKey(),
        'debit' => 0,
        'credit' => 50000,
    ]);

    $result = $journal->post();

    expect($result)->toBeTrue();
    expect($journal->fresh()->status)->toBe(JournalStatus::Posted);
});

it('fails to post unbalanced journal', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Unbalanced',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->getKey(),
        'account_id' => $this->debitAccount->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    $result = $journal->post();

    expect($result)->toBeFalse();
    expect($journal->fresh()->status)->toBe(JournalStatus::Draft);
});

it('voids a non-final journal', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Draft,
    ]);

    $result = $journal->void();

    expect($result)->toBeTrue();
    expect($journal->fresh()->status)->toBe(JournalStatus::Void);
});

it('cannot void a final journal', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test',
        'status' => JournalStatus::Void,
    ]);

    $result = $journal->void();

    expect($result)->toBeFalse();
});

it('scopes to posted journals', function () {
    Journal::query()->create(['date' => now(), 'description' => 'Posted', 'status' => JournalStatus::Posted]);
    Journal::query()->create(['date' => now(), 'description' => 'Draft', 'status' => JournalStatus::Draft]);

    expect(Journal::query()->posted()->count())->toBe(1);
});

it('scopes to draft journals', function () {
    Journal::query()->create(['date' => now(), 'description' => 'Posted', 'status' => JournalStatus::Posted]);
    Journal::query()->create(['date' => now(), 'description' => 'Draft', 'status' => JournalStatus::Draft]);

    expect(Journal::query()->draft()->count())->toBe(1);
});

it('scopes to tenant', function () {
    Journal::query()->create(['date' => now(), 'description' => 'Tenant 1', 'status' => JournalStatus::Draft, 'tenant_id' => 1]);
    Journal::query()->create(['date' => now(), 'description' => 'Tenant 2', 'status' => JournalStatus::Draft, 'tenant_id' => 2]);

    expect(Journal::query()->forTenant(1)->count())->toBe(1);
});

it('scopes by date range', function () {
    Journal::query()->create(['date' => '2026-01-15', 'description' => 'Jan', 'status' => JournalStatus::Draft]);
    Journal::query()->create(['date' => '2026-06-15', 'description' => 'Jun', 'status' => JournalStatus::Draft]);
    Journal::query()->create(['date' => '2026-12-15', 'description' => 'Dec', 'status' => JournalStatus::Draft]);

    expect(Journal::query()->dateBetween('2026-03-01', '2026-09-01')->count())->toBe(1);
});
