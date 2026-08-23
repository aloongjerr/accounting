<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Ledger\AccountLedger;
use AloongJerr\Accounting\Ledger\LedgerEntry;
use AloongJerr\Accounting\Ledger\TAccount;
use AloongJerr\Accounting\Models\Account;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

// ── LedgerEntry ──

it('creates LedgerEntry from row', function () {
    $row = (object) [
        'date' => '2024-06-15',
        'description' => 'Test entry',
        'debit' => 50000,
        'credit' => 0,
        'journal_id' => 1,
    ];

    $entry = LedgerEntry::fromRow($row, 100000);

    expect($entry->date)->toBeInstanceOf(Carbon::class);
    expect($entry->date->toDateString())->toBe('2024-06-15');
    expect($entry->description)->toBe('Test entry');
    expect($entry->debit)->toBe(50000);
    expect($entry->credit)->toBe(0);
    expect($entry->runningBalance)->toBe(100000);
    expect($entry->journalId)->toBe(1);
});

it('LedgerEntry defaults description to empty string', function () {
    $row = (object) [
        'date' => '2024-01-01',
        'debit' => 100,
        'credit' => 0,
        'journal_id' => 1,
    ];

    $entry = LedgerEntry::fromRow($row);

    expect($entry->description)->toBe('');
});

// ── AccountLedger ──

it('returns empty ledger when no transactions', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $ledger = new AccountLedger($account);

    expect($ledger->getEntries())->toBeEmpty();
    expect($ledger->getTotalDebit())->toBe(0);
    expect($ledger->getTotalCredit())->toBe(0);
});

it('returns entries after a transaction', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(500000, 'Payment')
        ->fromAccount($arAccount)
        ->to($cashAccount)
        ->commit();

    $ledger = new AccountLedger($cashAccount);
    $entries = $ledger->getEntries();

    expect($entries)->toHaveCount(1);
    expect($entries->first())->toBeInstanceOf(LedgerEntry::class);
    expect($entries->first()->debit)->toBe(500000);
});

it('calculates running balance correctly', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    // Two payments into cash
    Accounting::received(300000, 'Payment 1')->fromAccount($arAccount)->to($cashAccount)->commit();
    Accounting::received(200000, 'Payment 2')->fromAccount($arAccount)->to($cashAccount)->commit();

    $ledger = new AccountLedger($cashAccount);
    $entries = $ledger->getEntries();

    expect($entries)->toHaveCount(2);
    expect($entries[0]->runningBalance)->toBe(300000);
    expect($entries[1]->runningBalance)->toBe(500000);
});

it('filters ledger entries by period', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    // Create transactions on different dates relative to current year
    $janDate = Carbon::now()->startOfYear()->toDateString();
    $julDate = Carbon::now()->startOfYear()->addMonths(6)->toDateString();
    $j1 = Accounting::received(100000, 'Jan payment')->fromAccount($arAccount)->to($cashAccount)->commit();
    $j1->update(['date' => $janDate]);

    $j2 = Accounting::received(200000, 'Jul payment')->fromAccount($arAccount)->to($cashAccount)->commit();
    $j2->update(['date' => $julDate]);

    $ledger = new AccountLedger($cashAccount);
    $ledger->forPeriod(Carbon::now()->startOfYear()->addMonths(3), Carbon::now()->endOfYear());

    $entries = $ledger->getEntries();

    expect($entries)->toHaveCount(1);
    expect($entries->first()->debit)->toBe(200000);
});

it('calculates opening balance before period', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    // Create a transaction early in the year
    $janDate = Carbon::now()->startOfYear()->toDateString();
    $j1 = Accounting::received(100000, 'Jan payment')->fromAccount($arAccount)->to($cashAccount)->commit();
    $j1->update(['date' => $janDate]);

    // Ledger for later in the year should have opening balance from January
    $ledger = new AccountLedger($cashAccount);
    $ledger->forPeriod(Carbon::now()->startOfYear()->addMonths(6), Carbon::now()->endOfYear());

    expect($ledger->getOpeningBalance())->toBe(100000);
});

it('calculates closing balance through period end', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(100000, 'Payment 1')->fromAccount($arAccount)->to($cashAccount)->commit();
    Accounting::received(200000, 'Payment 2')->fromAccount($arAccount)->to($cashAccount)->commit();

    $ledger = new AccountLedger($cashAccount);
    $ledger->forPeriod(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());

    expect($ledger->getClosingBalance())->toBe(300000);
});

it('returns account from ledger', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $ledger = new AccountLedger($account);

    expect($ledger->getAccount()->getKey())->toBe($account->getKey());
});

it('opening balance is zero without period set', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $ledger = new AccountLedger($account);

    expect($ledger->getOpeningBalance())->toBe(0);
});

// ── TAccount ──

it('separates debit and credit entries', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    // Receive cash (debit cash, credit AR)
    Accounting::received(500000, 'Payment')
        ->fromAccount($arAccount)
        ->to($cashAccount)
        ->commit();

    $tAccount = new TAccount($cashAccount);
    $tAccount->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    expect($tAccount->getDebitEntries())->toHaveCount(1);
    expect($tAccount->getCreditEntries())->toBeEmpty();
    expect($tAccount->getTotalDebit())->toBe(500000);
    expect($tAccount->getTotalCredit())->toBe(0);
});

it('T-account calculates opening and closing balance', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(300000, 'Payment 1')->fromAccount($arAccount)->to($cashAccount)->commit();
    Accounting::received(200000, 'Payment 2')->fromAccount($arAccount)->to($cashAccount)->commit();

    $tAccount = new TAccount($cashAccount);
    $tAccount->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    expect($tAccount->getOpeningBalance())->toBe(0); // no prior entries
    expect($tAccount->getClosingBalance())->toBe(500000);
});

it('T-account returns account', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $tAccount = new TAccount($account);

    expect($tAccount->getAccount()->getKey())->toBe($account->getKey());
});

it('T-account via facade', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(100000, 'Payment')->fromAccount($arAccount)->to($cashAccount)->commit();

    $tAccount = Accounting::tAccount(AccountSystemKey::CashOnHand);
    $tAccount->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    expect($tAccount->getTotalDebit())->toBe(100000);
});

it('ledger via facade with AccountSystemKey', function () {
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(250000, 'Payment')->fromAccount($arAccount)->to($cashAccount)->commit();

    $ledger = Accounting::ledger(AccountSystemKey::CashOnHand);
    $entries = $ledger->getEntries();

    expect($entries)->toHaveCount(1);
    expect($entries->first()->debit)->toBe(250000);
});
