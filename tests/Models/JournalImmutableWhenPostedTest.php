<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Models\JournalEntry;

beforeEach(function () {
    $this->cash = Account::query()->create([
        'name' => 'Cash on Hand',
        'code' => '1101',
        'type' => AccountType::Account,
        'system_key' => AccountSystemKey::CashOnHand,
        'is_active' => true,
    ]);

    $this->revenue = Account::query()->create([
        'name' => 'Sales Revenue',
        'code' => '4101',
        'type' => AccountType::Account,
        'system_key' => AccountSystemKey::SalesRevenue,
        'is_active' => true,
    ]);
});

it('allows updating draft journal in any environment', function () {
    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test journal',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->revenue->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    // Should work in any environment
    $journal->update(['description' => 'Updated description']);

    expect($journal->fresh()->description)->toBe('Updated description');
});

it('prevents updating posted journal in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test journal',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->revenue->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    // Post the journal
    $journal->post();

    // Try to update - should throw exception
    $journal->update(['description' => 'Should not work']);
})->throws(\AloongJerr\Accounting\Exceptions\ImmutableAccountingException::class);

it('allows updating posted journal in non-production', function () {
    app()->detectEnvironment(fn () => 'testing');

    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test journal',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->revenue->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    // Post the journal
    $journal->post();

    // Should work in testing environment
    $journal->update(['description' => 'Updated in testing']);

    expect($journal->fresh()->description)->toBe('Updated in testing');
});

it('allows status change from draft to posted in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test journal',
        'status' => JournalStatus::Draft,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    JournalEntry::query()->create([
        'journal_id' => $journal->id,
        'account_id' => $this->revenue->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    // Should be able to post (change status from Draft to Posted)
    $result = $journal->post();

    expect($result)->toBeTrue();
    expect($journal->fresh()->status)->toBe(JournalStatus::Posted);
});

it('prevents updating voided journal in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $journal = Journal::query()->create([
        'date' => now(),
        'description' => 'Test journal',
        'status' => JournalStatus::Void,
    ]);

    // Void is a final status, should not be updatable
    $journal->update(['description' => 'Should not work']);
})->throws(\AloongJerr\Accounting\Exceptions\ImmutableAccountingException::class);
