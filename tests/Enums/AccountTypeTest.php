<?php

use AloongJerr\Accounting\Enums\AccountType;
use Filament\Support\Contracts\HasLabel;

it('has correct cases', function () {
    expect(AccountType::cases())->toHaveCount(3);
    expect(AccountType::Group->value)->toBe('group');
    expect(AccountType::Category->value)->toBe('category');
    expect(AccountType::Account->value)->toBe('account');
});

it('implements HasLabel contract', function () {
    expect(AccountType::Group)->toBeInstanceOf(HasLabel::class);
});

it('returns label via translation', function () {
    $label = AccountType::Group->getLabel();

    expect($label)->toBeString();
    expect($label)->not->toBeEmpty();
});

it('can be created from value', function () {
    expect(AccountType::from('group'))->toBe(AccountType::Group);
    expect(AccountType::from('category'))->toBe(AccountType::Category);
    expect(AccountType::from('account'))->toBe(AccountType::Account);
});

it('throws on invalid value', function () {
    AccountType::from('invalid');
})->throws(ValueError::class);
