<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Reports\TrialBalance;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('returns empty trial balance when no transactions', function () {
    $report = new TrialBalance();
    $data = $report->asOf(Carbon::today())->get();

    expect($data)->toBeEmpty();
});

it('returns balances after transactions', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(500000, 'Payment')
        ->fromAccount($arAccount)
        ->commit();

    $report = new TrialBalance();
    $data = $report->asOf(Carbon::today())->get();

    expect($data)->not->toBeEmpty();
});

it('summary shows balanced totals', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    // Receive cash: debit CashOnHand (default), credit AR system account
    Accounting::received(500000, 'Payment')
        ->fromAccount($arAccount)
        ->commit();

    $report = new TrialBalance();
    $summary = $report->asOf(Carbon::today())->summary();

    expect($summary->total_debit)->toBe(500000);
    expect($summary->total_credit)->toBe(500000);
    expect($summary->is_balanced)->toBeTrue();
    expect($summary->difference)->toBe(0);
});

it('includes rolled up parent accounts', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    Accounting::received(100000, 'Payment')->fromAccount($arAccount)->commit();

    $report = new TrialBalance();
    $data = $report->asOf(Carbon::today())->get();

    // Should have leaf accounts + parent rollups (CurrentAssets, Assets, etc.)
    $hasGroupOrCategory = $data->filter(fn ($item) => in_array($item->accountType->value, ['group', 'category']))->isNotEmpty();
    expect($hasGroupOrCategory)->toBeTrue();
});

it('uses fiscal year dates', function () {
    $report = new TrialBalance();
    $report->forFiscalYear(2024);

    // Default config: Jan 1 - Dec 31
    $data = $report->get();

    // Just verify it runs without error
    expect($data)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('creates via facade', function () {
    $report = Accounting::trialBalance();

    expect($report)->toBeInstanceOf(TrialBalance::class);
});
