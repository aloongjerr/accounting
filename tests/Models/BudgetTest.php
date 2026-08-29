<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Budget;
use Carbon\Carbon;

beforeEach(function () {
    $this->account = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();

    if (! $this->account) {
        $this->account = Account::query()->create([
            'name' => 'Rent Expense',
            'code' => '5001',
            'type' => AccountType::Account,
            'system_key' => AccountSystemKey::RentExpense,
            'is_active' => true,
        ]);
    }
});

it('creates a budget with correct attributes', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 1200000,
        'description' => 'Annual rent budget',
    ]);

    expect($budget->account_id)->toBe($this->account->id);
    expect($budget->amount)->toBe(1200000);
    expect($budget->description)->toBe('Annual rent budget');
    expect($budget->start_date)->toBeInstanceOf(Carbon::class);
    expect($budget->end_date)->toBeInstanceOf(Carbon::class);
});

it('belongs to an account', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 100000,
    ]);

    expect($budget->account)->toBeInstanceOf(Account::class);
    expect($budget->account->id)->toBe($this->account->id);
});

it('casts dates to Carbon instances', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 100000,
    ]);

    $budget = $budget->fresh();

    expect($budget->start_date)->toBeInstanceOf(Carbon::class);
    expect($budget->end_date)->toBeInstanceOf(Carbon::class);
});

it('casts amount to integer', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 500000,
    ]);

    $budget = $budget->fresh();

    expect($budget->amount)->toBeInt();
    expect($budget->amount)->toBe(500000);
});

it('scopes to account', function () {
    Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 100000,
    ]);

    $budgets = Budget::query()->forAccount($this->account->id)->get();

    expect($budgets)->toHaveCount(1);
    expect($budgets->first()->account_id)->toBe($this->account->id);
});

it('scopes to period', function () {
    Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-03-31',
        'amount' => 100000,
    ]);

    Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-06-01',
        'end_date' => '2024-06-30',
        'amount' => 50000,
    ]);

    $budgets = Budget::query()
        ->forPeriod('2024-01-01', '2024-03-31')
        ->get();

    expect($budgets)->toHaveCount(1);
});

it('scopes to tenant', function () {
    Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 100000,
        'tenant_id' => 1,
    ]);

    Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 200000,
        'tenant_id' => 2,
    ]);

    $budgets = Budget::query()->forTenant(1)->get();

    expect($budgets)->toHaveCount(1);
    expect($budgets->first()->amount)->toBe(100000);
});

it('detects overlapping periods', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-06-30',
        'amount' => 100000,
    ]);

    expect($budget->overlaps(Carbon::parse('2024-03-01'), Carbon::parse('2024-05-31')))->toBeTrue();
    expect($budget->overlaps(Carbon::parse('2024-07-01'), Carbon::parse('2024-12-31')))->toBeFalse();
});

it('allows null description', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 100000,
    ]);

    expect($budget->description)->toBeNull();
});

it('casts tenant_id to integer', function () {
    $budget = Budget::query()->create([
        'account_id' => $this->account->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'amount' => 100000,
        'tenant_id' => 5,
    ]);

    $budget = $budget->fresh();

    expect($budget->tenant_id)->toBeInt();
    expect($budget->tenant_id)->toBe(5);
});
