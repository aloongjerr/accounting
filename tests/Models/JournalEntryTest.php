<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Models\JournalEntry;

beforeEach(function () {
    $this->account = Account::query()->create([
        'name' => 'Cash on Hand',
        'code' => '1101',
        'type' => AccountType::Account,
        'system_key' => AccountSystemKey::CashOnHand,
        'is_active' => true,
    ]);

    $this->journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test journal',
        'status' => JournalStatus::Draft,
    ]);
});

it('has correct fillable attributes', function () {
    $entry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 100000,
        'credit' => 0,
        'description' => 'Cash received',
    ]);

    expect($entry->journal_id)->toBe($this->journal->getKey());
    expect($entry->account_id)->toBe($this->account->getKey());
    expect($entry->debit)->toBe(100000);
    expect($entry->credit)->toBe(0);
    expect($entry->description)->toBe('Cash received');
});

it('casts debit and credit to integer', function () {
    $entry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 150050,
        'credit' => 0,
    ]);

    $fresh = JournalEntry::find($entry->getKey());

    expect($fresh->debit)->toBe(150050);
    expect($fresh->credit)->toBe(0);
});

it('belongs to a journal', function () {
    $entry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 10000,
        'credit' => 0,
    ]);

    expect($entry->journal)->toBeInstanceOf(Journal::class);
    expect($entry->journal->id)->toBe($this->journal->id);
});

it('belongs to an account', function () {
    $entry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 10000,
        'credit' => 0,
    ]);

    expect($entry->account)->toBeInstanceOf(Account::class);
    expect($entry->account->id)->toBe($this->account->id);
});

it('calculates net amount', function () {
    $debitEntry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    expect($debitEntry->netAmount())->toBe(100000);

    $creditEntry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 0,
        'credit' => 50000,
    ]);

    expect($creditEntry->netAmount())->toBe(-50000);
});

it('identifies debit entries', function () {
    $debitEntry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    $creditEntry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 0,
        'credit' => 50000,
    ]);

    expect($debitEntry->isDebit())->toBeTrue();
    expect($creditEntry->isDebit())->toBeFalse();
});

it('identifies credit entries', function () {
    $debitEntry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 100000,
        'credit' => 0,
    ]);

    $creditEntry = JournalEntry::query()->create([
        'journal_id' => $this->journal->getKey(),
        'account_id' => $this->account->getKey(),
        'debit' => 0,
        'credit' => 50000,
    ]);

    expect($debitEntry->isCredit())->toBeFalse();
    expect($creditEntry->isCredit())->toBeTrue();
});
