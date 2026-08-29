<?php

use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Events\JournalTransferredEvent;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
});

// ── Basic Transfer ──

it('transfer: creates balanced entries with default fromCash toBank', function () {
    $journal = Accounting::transfer(500000, 'Cash to bank deposit')
        ->commit();

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebit())->toBe(500000);
    expect($journal->totalCredit())->toBe(500000);
    expect($journal->entries)->toHaveCount(2);
    expect($journal->status)->toBe(JournalStatus::Draft);
});

it('transfer: debits cash in bank by default', function () {
    $journal = Accounting::transfer(500000, 'Deposit')
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    expect($debitEntry->account_id)->toBe($bankAccount->getKey());
});

it('transfer: credits cash on hand by default', function () {
    $journal = Accounting::transfer(500000, 'Deposit')
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    expect($creditEntry->account_id)->toBe($cashAccount->getKey());
});

// ── fromBank / toCash ──

it('transfer: supports fromBank toCash (withdrawal)', function () {
    $journal = Accounting::transfer(200000, 'ATM withdrawal')
        ->fromBank()
        ->toCash()
        ->commit();

    expect($journal->isBalanced())->toBeTrue();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);

    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    expect($debitEntry->account_id)->toBe($cashAccount->getKey());
    expect($creditEntry->account_id)->toBe($bankAccount->getKey());
});

// ── Custom system keys ──

it('transfer: supports custom fromSystemKey', function () {
    $journal = Accounting::transfer(300000, 'Transfer from prepaid')
        ->fromSystemKey(AccountSystemKey::PrepaidExpenses)
        ->toBank()
        ->commit();

    expect($journal->isBalanced())->toBeTrue();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $prepaidAccount = Account::query()->where('system_key', AccountSystemKey::PrepaidExpenses)->first();

    expect($creditEntry->account_id)->toBe($prepaidAccount->getKey());
});

it('transfer: supports custom toSystemKey', function () {
    $journal = Accounting::transfer(100000, 'Transfer to petty cash')
        ->fromBank()
        ->toSystemKey(AccountSystemKey::CashOnHand)
        ->commit();

    expect($journal->isBalanced())->toBeTrue();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    expect($debitEntry->account_id)->toBe($cashAccount->getKey());
});

// ── Explicit Account models ──

it('transfer: supports from() with specific account', function () {
    $sourceAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();
    $destAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    $journal = Accounting::transfer(150000, 'Custom transfer')
        ->from($sourceAccount)
        ->to($destAccount)
        ->commit();

    expect($journal->isBalanced())->toBeTrue();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);

    expect($debitEntry->account_id)->toBe($destAccount->getKey());
    expect($creditEntry->account_id)->toBe($sourceAccount->getKey());
});

// ── Same account guard ──

it('transfer: throws when source and destination are the same', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    Accounting::transfer(100000, 'Bad transfer')
        ->from($account)
        ->to($account)
        ->commit();
})->throws(\LogicException::class, 'Cannot transfer to the same account');

it('transfer: throws when default system keys resolve to same account', function () {
    // Force both to CashOnHand
    Accounting::transfer(100000, 'Bad transfer')
        ->fromCash()
        ->toCash()
        ->commit();
})->throws(\LogicException::class, 'Cannot transfer to the same account');

// ── Event ──

it('transfer: fires JournalTransferredEvent', function () {
    Event::fake([JournalTransferredEvent::class]);

    $journal = Accounting::transfer(500000, 'Deposit')
        ->commit();

    Event::assertDispatched(JournalTransferredEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

// ── Description ──

it('transfer: sets description on entries', function () {
    $journal = Accounting::transfer(500000, 'Office cash top-up')
        ->commit();

    expect($journal->entries->first()->description)->toBe('Office cash top-up');
    expect($journal->entries->last()->description)->toBe('Office cash top-up');
});

// ── Tenant support ──

it('transfer: supports tenant id', function () {
    $transaction = Accounting::transfer(500000, 'Tenant transfer')
        ->forTenant(1);

    expect($transaction->getTenantId())->toBe(1);
});
