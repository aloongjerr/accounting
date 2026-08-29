<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Budget;
use AloongJerr\Accounting\Reports\BudgetReport;
use AloongJerr\Accounting\ValueObjects\BudgetRow;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('returns empty report when no budgets exist', function () {
    $report = new BudgetReport();
    $data = $report
        ->forPeriod('2024-01-01', '2024-12-31')
        ->get();

    expect($data)->toBeEmpty();
});

it('shows budget vs actual for expense account', function () {
    $rentAccount = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();
    $today = Carbon::today();

    // Create a budget for rent expense
    Budget::query()->create([
        'account_id' => $rentAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 500000, // $5,000 budgeted
    ]);

    // Create actual spending: paid $3,000 for rent
    $journal = Accounting::purchased(300000, 'Rent payment')
        ->forExpense(AccountSystemKey::RentExpense)
        ->forCash()
        ->onDate($today->copy()->subMonths(2))
        ->commit();
    $journal->post();

    $report = new BudgetReport();
    $data = $report
        ->forPeriod($today->copy()->startOfYear(), $today->copy()->endOfYear())
        ->get();

    expect($data)->toHaveCount(1);

    $row = $data->first();
    expect($row)->toBeInstanceOf(BudgetRow::class);
    expect($row->accountId)->toBe($rentAccount->id);
    expect($row->budgeted)->toBe(500000);
    expect($row->actual)->toBe(300000);
    expect($row->variance)->toBe(200000); // Under budget by $2,000
});

it('calculates variance percentage', function () {
    $rentAccount = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();
    $today = Carbon::today();

    Budget::query()->create([
        'account_id' => $rentAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 400000, // $4,000 budgeted
    ]);

    // Spent $1,000 (25% of budget)
    $journal = Accounting::purchased(100000, 'Rent payment')
        ->forExpense(AccountSystemKey::RentExpense)
        ->forCash()
        ->onDate($today->copy()->subMonth())
        ->commit();
    $journal->post();

    $report = new BudgetReport();
    $data = $report
        ->forPeriod($today->copy()->startOfYear(), $today->copy()->endOfYear())
        ->get();

    $row = $data->first();
    expect($row->variance)->toBe(300000);
    expect($row->variancePercentage)->toBe(75.0); // 75% under budget
});

it('shows zero actual when no transactions exist', function () {
    $rentAccount = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();
    $today = Carbon::today();

    Budget::query()->create([
        'account_id' => $rentAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 200000,
    ]);

    $report = new BudgetReport();
    $data = $report
        ->forPeriod($today->copy()->startOfYear(), $today->copy()->endOfYear())
        ->get();

    expect($data)->toHaveCount(1);
    expect($data->first()->actual)->toBe(0);
    expect($data->first()->variance)->toBe(200000);
});

it('summary returns correct totals', function () {
    $rentAccount = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();
    $salaryAccount = Account::query()->where('system_key', AccountSystemKey::SalaryExpense)->first();
    $today = Carbon::today();

    Budget::query()->create([
        'account_id' => $rentAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 500000,
    ]);

    Budget::query()->create([
        'account_id' => $salaryAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 300000,
    ]);

    // Spend $200,000 on rent
    $j1 = Accounting::purchased(200000, 'Rent')
        ->forExpense(AccountSystemKey::RentExpense)
        ->forCash()
        ->onDate($today->copy()->subMonth())
        ->commit();
    $j1->post();

    // Spend $100,000 on salary
    $j2 = Accounting::purchased(100000, 'Salary')
        ->forExpense(AccountSystemKey::SalaryExpense)
        ->forCash()
        ->onDate($today->copy()->subMonth())
        ->commit();
    $j2->post();

    $report = new BudgetReport();
    $summary = $report
        ->forPeriod($today->copy()->startOfYear(), $today->copy()->endOfYear())
        ->summary();

    expect($summary->total_budgeted)->toBe(800000);
    expect($summary->total_actual)->toBe(300000);
    expect($summary->total_variance)->toBe(500000);
});

it('summary returns zero totals when no budgets', function () {
    $report = new BudgetReport();
    $summary = $report
        ->forPeriod('2024-01-01', '2024-12-31')
        ->summary();

    expect($summary->total_budgeted)->toBe(0);
    expect($summary->total_actual)->toBe(0);
    expect($summary->total_variance)->toBe(0);
    expect($summary->overall_percentage)->toBeNull();
});

it('creates via facade', function () {
    $report = Accounting::budgetReport();

    expect($report)->toBeInstanceOf(BudgetReport::class);
});

it('sorts rows by account code', function () {
    $rentAccount = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();
    $salaryAccount = Account::query()->where('system_key', AccountSystemKey::SalaryExpense)->first();
    $today = Carbon::today();

    // Create budgets in reverse code order
    Budget::query()->create([
        'account_id' => $salaryAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 300000,
    ]);

    Budget::query()->create([
        'account_id' => $rentAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 500000,
    ]);

    $report = new BudgetReport();
    $data = $report
        ->forPeriod($today->copy()->startOfYear(), $today->copy()->endOfYear())
        ->get();

    expect($data)->toHaveCount(2);
    // Rent (5001) should come before Salary (5051)
    expect($data->first()->accountCode)->toBeLessThanOrEqual($data->last()->accountCode);
});

it('handles over-budget scenario', function () {
    $rentAccount = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();
    $today = Carbon::today();

    Budget::query()->create([
        'account_id' => $rentAccount->id,
        'start_date' => $today->copy()->startOfYear(),
        'end_date' => $today->copy()->endOfYear(),
        'amount' => 100000, // $1,000 budgeted
    ]);

    // Spend $1,500 (over budget)
    $journal = Accounting::purchased(150000, 'Rent over budget')
        ->forExpense(AccountSystemKey::RentExpense)
        ->forCash()
        ->onDate($today->copy()->subMonth())
        ->commit();
    $journal->post();

    $report = new BudgetReport();
    $data = $report
        ->forPeriod($today->copy()->startOfYear(), $today->copy()->endOfYear())
        ->get();

    $row = $data->first();
    expect($row->budgeted)->toBe(100000);
    expect($row->actual)->toBe(150000);
    expect($row->variance)->toBe(-50000); // Over budget (negative variance)
    expect($row->variancePercentage)->toBe(-50.0); // -50% (over budget)
});
