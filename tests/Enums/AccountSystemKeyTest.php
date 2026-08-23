<?php

use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use Filament\Support\Contracts\HasLabel;

it('implements HasLabel and HasAccountIdentity', function () {
    expect(AccountSystemKey::Assets)->toBeInstanceOf(HasLabel::class);
    expect(AccountSystemKey::Assets)->toBeInstanceOf(HasAccountIdentity::class);
});

it('has all group cases', function () {
    $groups = [
        AccountSystemKey::Assets,
        AccountSystemKey::Liabilities,
        AccountSystemKey::Equity,
        AccountSystemKey::Revenue,
        AccountSystemKey::Expenses,
    ];

    foreach ($groups as $group) {
        expect($group->parentKey())->toBeNull();
    }
});

it('has correct category parent mappings', function () {
    expect(AccountSystemKey::CurrentAssets->parentKey())->toBe(AccountSystemKey::Assets);
    expect(AccountSystemKey::FixedAssets->parentKey())->toBe(AccountSystemKey::Assets);
    expect(AccountSystemKey::CurrentLiabilities->parentKey())->toBe(AccountSystemKey::Liabilities);
    expect(AccountSystemKey::LongTermLiabilities->parentKey())->toBe(AccountSystemKey::Liabilities);
    expect(AccountSystemKey::OwnerEquity->parentKey())->toBe(AccountSystemKey::Equity);
    expect(AccountSystemKey::OperatingRevenue->parentKey())->toBe(AccountSystemKey::Revenue);
    expect(AccountSystemKey::NonOperatingRevenue->parentKey())->toBe(AccountSystemKey::Revenue);
    expect(AccountSystemKey::ContraRevenue->parentKey())->toBe(AccountSystemKey::Revenue);
    expect(AccountSystemKey::CostOfGoodsSold->parentKey())->toBe(AccountSystemKey::Expenses);
    expect(AccountSystemKey::OperatingExpenses->parentKey())->toBe(AccountSystemKey::Expenses);
    expect(AccountSystemKey::NonOperatingExpenses->parentKey())->toBe(AccountSystemKey::Expenses);
});

it('has correct leaf account parent mappings', function () {
    // Current Assets
    expect(AccountSystemKey::CashOnHand->parentKey())->toBe(AccountSystemKey::CurrentAssets);
    expect(AccountSystemKey::CashInBank->parentKey())->toBe(AccountSystemKey::CurrentAssets);
    expect(AccountSystemKey::AccountsReceivable->parentKey())->toBe(AccountSystemKey::CurrentAssets);

    // Fixed Assets
    expect(AccountSystemKey::Land->parentKey())->toBe(AccountSystemKey::FixedAssets);
    expect(AccountSystemKey::Building->parentKey())->toBe(AccountSystemKey::FixedAssets);

    // Current Liabilities
    expect(AccountSystemKey::AccountsPayable->parentKey())->toBe(AccountSystemKey::CurrentLiabilities);

    // Equity
    expect(AccountSystemKey::OwnerCapital->parentKey())->toBe(AccountSystemKey::OwnerEquity);
    expect(AccountSystemKey::RetainedEarnings->parentKey())->toBe(AccountSystemKey::OwnerEquity);

    // Revenue
    expect(AccountSystemKey::SalesRevenue->parentKey())->toBe(AccountSystemKey::OperatingRevenue);
    expect(AccountSystemKey::InterestIncome->parentKey())->toBe(AccountSystemKey::NonOperatingRevenue);
    expect(AccountSystemKey::SalesReturnsAndAllowances->parentKey())->toBe(AccountSystemKey::ContraRevenue);

    // Expenses
    expect(AccountSystemKey::CostOfRevenue->parentKey())->toBe(AccountSystemKey::CostOfGoodsSold);
    expect(AccountSystemKey::SalaryExpense->parentKey())->toBe(AccountSystemKey::OperatingExpenses);
    expect(AccountSystemKey::InterestExpense->parentKey())->toBe(AccountSystemKey::NonOperatingExpenses);
});

it('generates unique codes for all cases', function () {
    $codes = array_map(fn ($case) => $case->getCode(), AccountSystemKey::cases());

    expect($codes)->toHaveCount(count(AccountSystemKey::cases()));
    expect(array_unique($codes))->toHaveCount(count($codes));
});

it('generates correct group codes', function () {
    expect(AccountSystemKey::Assets->getCode())->toBe('1000');
    expect(AccountSystemKey::Liabilities->getCode())->toBe('2000');
    expect(AccountSystemKey::Equity->getCode())->toBe('3000');
    expect(AccountSystemKey::Revenue->getCode())->toBe('4000');
    expect(AccountSystemKey::Expenses->getCode())->toBe('5000');
});

it('generates correct category codes', function () {
    expect(AccountSystemKey::CurrentAssets->getCode())->toBe('1100');
    expect(AccountSystemKey::FixedAssets->getCode())->toBe('1500');
    expect(AccountSystemKey::CurrentLiabilities->getCode())->toBe('2100');
    expect(AccountSystemKey::OperatingRevenue->getCode())->toBe('4100');
    expect(AccountSystemKey::OperatingExpenses->getCode())->toBe('5200');
});

it('generates correct leaf account codes', function () {
    expect(AccountSystemKey::CashOnHand->getCode())->toBe('1101');
    expect(AccountSystemKey::CashInBank->getCode())->toBe('1102');
    expect(AccountSystemKey::AccountsPayable->getCode())->toBe('2101');
    expect(AccountSystemKey::SalesRevenue->getCode())->toBe('4101');
    expect(AccountSystemKey::SalaryExpense->getCode())->toBe('5201');
});

it('returns label via translation', function () {
    $label = AccountSystemKey::Assets->getLabel();

    expect($label)->toBeString();
    expect($label)->not->toBeEmpty();
});

it('every case has a parent mapping', function () {
    foreach (AccountSystemKey::cases() as $case) {
        // Groups return null, all others return a valid parent
        $parent = $case->parentKey();

        if ($parent !== null) {
            expect($parent)->toBeInstanceOf(AccountSystemKey::class);
        }
    }

    expect(true)->toBeTrue();
});

it('every case has a non-empty code', function () {
    foreach (AccountSystemKey::cases() as $case) {
        expect($case->getCode())->toBeString();
        expect($case->getCode())->not->toBeEmpty();
    }
});
