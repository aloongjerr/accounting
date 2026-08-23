<?php

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\AccountSnapshot;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Models\JournalEntry;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('allows journal deletion in non-production environments', function () {
    // Default test environment is 'testing', not 'production'
    expect(app()->environment())->not->toBe('production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    expect(Journal::count())->toBe(1);

    // Deletion should work in testing environment
    Journal::first()->delete();

    expect(Journal::count())->toBe(0);
});

it('prevents journal deletion in production', function () {
    // Mock production environment
    app()->detectEnvironment(fn () => 'production');
    expect(app()->environment())->toBe('production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    expect(Journal::count())->toBe(1);

    // Deletion should throw exception in production
    Journal::first()->delete();
})->throws(\AloongJerr\Accounting\Exceptions\ImmutableAccountingException::class);

it('allows journal entry deletion in non-production environments', function () {
    expect(app()->environment())->not->toBe('production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    expect(JournalEntry::count())->toBeGreaterThan(0);

    // Deletion should work in testing environment
    JournalEntry::first()->delete();

    expect(JournalEntry::count())->toBeLessThan(2);
});

it('prevents journal entry deletion in production', function () {
    app()->detectEnvironment(fn () => 'production');
    expect(app()->environment())->toBe('production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    expect(JournalEntry::count())->toBeGreaterThan(0);

    // Deletion should throw exception in production
    JournalEntry::first()->delete();
})->throws(\AloongJerr\Accounting\Exceptions\ImmutableAccountingException::class);

it('allows voiding journal instead of deletion in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    $journal = Journal::first();

    // Voiding should work even in production (alternative to deletion)
    $journal->void();

    expect($journal->fresh()->status)->toBe(JournalStatus::Void);
    expect(Journal::count())->toBe(1); // Journal still exists, not deleted
});

it('allows mass deletion in non-production environments', function () {
    expect(app()->environment())->not->toBe('production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(10000, 'Payment 1')->fromAccount($arAccount)->commit();
    Accounting::received(20000, 'Payment 2')->fromAccount($arAccount)->commit();

    expect(Journal::count())->toBe(2);

    // Mass deletion should work in testing
    Journal::query()->delete();

    expect(Journal::count())->toBe(0);
});

it('prevents mass deletion in production via model iteration', function () {
    app()->detectEnvironment(fn () => 'production');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(10000, 'Payment 1')->fromAccount($arAccount)->commit();
    Accounting::received(20000, 'Payment 2')->fromAccount($arAccount)->commit();

    expect(Journal::count())->toBe(2);

    // Iterating and deleting each model fires the deleting event
    Journal::all()->each(fn (Journal $j) => $j->delete());
})->throws(\LogicException::class);

it('prevents account snapshot deletion in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // Create a snapshot directly
    AccountSnapshot::create([
        'snapshot_date' => now(),
        'company_id' => null,
        'snapshot_type' => 'daily',
        'data' => ['test' => ['debit' => 100, 'credit' => 0, 'balance' => 100]],
    ]);

    expect(AccountSnapshot::count())->toBe(1);

    // Deletion should throw exception in production
    AccountSnapshot::first()->delete();
})->throws(\AloongJerr\Accounting\Exceptions\ImmutableAccountingException::class);

it('allows account snapshot deletion in non-production environments', function () {
    expect(app()->environment())->not->toBe('production');

    AccountSnapshot::create([
        'snapshot_date' => now(),
        'company_id' => null,
        'snapshot_type' => 'daily',
        'data' => ['test' => ['debit' => 100, 'credit' => 0, 'balance' => 100]],
    ]);

    expect(AccountSnapshot::count())->toBe(1);

    // Deletion should work in testing environment
    AccountSnapshot::first()->delete();

    expect(AccountSnapshot::count())->toBe(0);
});
