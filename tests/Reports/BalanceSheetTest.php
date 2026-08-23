<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Reports\BalanceSheet;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('returns empty balance sheet when no transactions', function () {
    $report = new BalanceSheet();
    $data = $report->asOf(Carbon::today())->get();

    expect($data)->toBeEmpty();
});

it('shows assets from cash transactions', function () {
    // Owner invests cash: debit CashOnHand, credit OwnerCapital
    $cash = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $capital = Account::query()->where('system_key', AccountSystemKey::OwnerCapital)->first();

    Accounting::journal('Owner investment')
        ->debit($cash, 1000000)
        ->credit($capital, 1000000)
        ->commit();

    $report = new BalanceSheet();
    $data = $report->asOf(Carbon::today())->get();

    expect($data)->not->toBeEmpty();

    // Should contain asset accounts
    $hasAssets = $data->filter(fn ($item) => $item->systemKey === AccountSystemKey::CashOnHand)->isNotEmpty();
    expect($hasAssets)->toBeTrue();
});

it('summary calculates assets, liabilities, equity', function () {
    $cash = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $capital = Account::query()->where('system_key', AccountSystemKey::OwnerCapital)->first();
    $ap = Account::query()->where('system_key', AccountSystemKey::AccountsPayable)->first();

    // 1. Owner invests 1,000,000 cash → Assets up, Equity up
    Accounting::journal('Investment')
        ->debit($cash, 1000000)
        ->credit($capital, 1000000)
        ->commit();

    // 2. Cash sale: debit CashOnHand, credit SalesRevenue (500,000)
    Accounting::sold(500000, 'Sale')->forCash()->commit();

    // 3. Purchase supplies on credit: debit RentExpense, credit AP system account (200,000)
    Accounting::journal('Rent accrual')
        ->debit(Account::query()->where('system_key', AccountSystemKey::RentExpense)->first(), 200000)
        ->credit($ap, 200000)
        ->commit();

    $report = new BalanceSheet();
    $report->asOf(Carbon::today());
    $summary = $report->summary();

    // Assets: Cash 1,000,000 + 500,000 = 1,500,000
    expect($summary->assets)->toBe(1500000);

    // Liabilities: AP 200,000
    expect($summary->liabilities)->toBe(200000);

    // Retained earnings: Revenue 500,000 - Expense 200,000 = 300,000
    expect($summary->retained_earnings)->toBe(300000);

    // Equity: OwnerCapital 1,000,000 + Retained Earnings 300,000 = 1,300,000
    expect($summary->equity)->toBe(1300000);

    // Balanced: Assets (1,500,000) = Liabilities (200,000) + Equity (1,300,000)
    expect($summary->is_balanced)->toBeTrue();
    expect($summary->difference)->toBe(0);
});

it('uses fiscal year dates', function () {
    $cash = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();
    $capital = Account::query()->where('system_key', AccountSystemKey::OwnerCapital)->first();

    Accounting::journal('Investment')
        ->debit($cash, 500000)
        ->credit($capital, 500000)
        ->commit();

    $report = new BalanceSheet();
    $report->forFiscalYear(Carbon::now()->year);

    $summary = $report->summary();

    expect($summary->assets)->toBe(500000);
});

it('creates via facade', function () {
    $report = Accounting::balanceSheet();

    expect($report)->toBeInstanceOf(BalanceSheet::class);
});
