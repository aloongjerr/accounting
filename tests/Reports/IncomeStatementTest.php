<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Reports\IncomeStatement;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('returns empty income statement when no transactions', function () {
    $report = new IncomeStatement();
    $data = $report->forPeriod('2024-01-01', '2024-12-31')->get();

    expect($data)->toBeEmpty();
});

it('shows revenue from sold transaction', function () {
    // Cash sale: debit CashOnHand, credit SalesRevenue
    Accounting::sold(500000, 'Product sale')->forCash()->commit();

    $report = new IncomeStatement();
    $report->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    $data = $report->get();
    $summary = $report->summary();

    expect($data)->not->toBeEmpty();
    expect($summary->revenue)->toBe(500000);
    expect($summary->expenses)->toBe(0);
    expect($summary->net_profit)->toBe(500000);
});

it('shows expenses from purchased transaction', function () {
    // Cash purchase of supplies: debit OfficeSuppliesExpense, credit CashOnHand
    Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->forCash()
        ->commit();

    $report = new IncomeStatement();
    $report->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    $summary = $report->summary();

    expect($summary->revenue)->toBe(0);
    expect($summary->expenses)->toBe(200000);
    expect($summary->net_profit)->toBe(-200000);
});

it('calculates net profit correctly', function () {
    // Revenue: 500000
    Accounting::sold(500000, 'Sale')->forCash()->commit();

    // Expense: 200000
    Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->forCash()
        ->commit();

    $report = new IncomeStatement();
    $report->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    $summary = $report->summary();

    expect($summary->revenue)->toBe(500000);
    expect($summary->expenses)->toBe(200000);
    expect($summary->net_profit)->toBe(300000);
});

it('filters out non-income accounts', function () {
    // Asset transaction (received cash) — should not appear in income statement
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();
    Accounting::received(1000000, 'Cash injection')->fromAccount($arAccount)->commit();

    $report = new IncomeStatement();
    $report->forPeriod(Carbon::parse('2020-01-01'), Carbon::parse('2030-12-31'));

    $data = $report->get();
    $summary = $report->summary();

    // CashOnHand is an asset, not income
    expect($summary->revenue)->toBe(0);
    expect($summary->expenses)->toBe(0);
});

it('uses fiscal year dates', function () {
    Accounting::sold(100000, 'Sale')->forCash()->commit();

    $report = new IncomeStatement();
    $report->forFiscalYear(Carbon::now()->year);

    $summary = $report->summary();

    expect($summary->revenue)->toBe(100000);
});

it('creates via facade', function () {
    $report = Accounting::incomeStatement();

    expect($report)->toBeInstanceOf(IncomeStatement::class);
});
