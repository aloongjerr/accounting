<?php

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Resolvers\AccountResolver;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->resolver = new AccountResolver;
});

it('resolves a system account by key', function () {
    $account = $this->resolver->resolveSystemAccount(AccountSystemKey::CashOnHand);

    expect($account)->toBeInstanceOf(Account::class);
    expect($account->system_key)->toBe(AccountSystemKey::CashOnHand);
    expect($account->code)->toBe('1101');
});

it('throws when system account not found', function () {
    // Delete the account first
    Account::query()->where('system_key', AccountSystemKey::CashOnHand)->delete();

    $this->resolver->resolveSystemAccount(AccountSystemKey::CashOnHand);
})->throws(\RuntimeException::class, 'not found');

it('resolves system account with company filter', function () {
    // System account without company should be found with null companyId
    $account = $this->resolver->resolveSystemAccount(AccountSystemKey::CashOnHand, null);

    expect($account)->toBeInstanceOf(Account::class);
    expect($account->company_id)->toBeNull();
});

it('resolves parent account by key', function () {
    $parent = $this->resolver->resolveParentAccount(AccountSystemKey::CurrentAssets);

    expect($parent)->toBeInstanceOf(Account::class);
    expect($parent->system_key)->toBe(AccountSystemKey::CurrentAssets);
    expect($parent->type)->toBe(AccountType::Category);
});

it('throws when parent account not found', function () {
    Account::query()->where('system_key', AccountSystemKey::CurrentAssets)->delete();

    $this->resolver->resolveParentAccount(AccountSystemKey::CurrentAssets);
})->throws(\RuntimeException::class, 'not found');

it('resolves entity account and creates if not exists', function () {
    $entity = new class implements Accountable
    {
        public function getAccountKeys(): BackedEnum|array|\AloongJerr\Accounting\Enums\AccountSystemKey
        {
            return AccountSystemKey::AccountsReceivable;
        }

        public function getAccountIdentifier(): array
        {
            return ['id' => 999, 'name' => 'Test Customer'];
        }
    };

    $account = $this->resolver->resolveEntityAccount($entity, AccountSystemKey::CurrentAssets);

    expect($account)->toBeInstanceOf(Account::class);
    expect($account->name)->toBe('Test Customer');
    expect($account->model_type)->toBe(get_class($entity));
    expect($account->model_id)->toBe(999);
    expect($account->type)->toBe(AccountType::Account);
    expect($account->parent->system_key)->toBe(AccountSystemKey::CurrentAssets);
});

it('returns existing entity account on second resolve', function () {
    $entity = new class implements Accountable
    {
        public function getAccountKeys(): BackedEnum|array|\AloongJerr\Accounting\Enums\AccountSystemKey
        {
            return AccountSystemKey::AccountsReceivable;
        }

        public function getAccountIdentifier(): array
        {
            return ['id' => 888, 'name' => 'Repeat Customer'];
        }
    };

    $first = $this->resolver->resolveEntityAccount($entity, AccountSystemKey::CurrentAssets);
    $second = $this->resolver->resolveEntityAccount($entity, AccountSystemKey::CurrentAssets);

    expect($second->getKey())->toBe($first->getKey());
});

it('creates separate accounts for same entity under different parents', function () {
    $entity = new class implements Accountable
    {
        public function getAccountKeys(): BackedEnum|array|\AloongJerr\Accounting\Enums\AccountSystemKey
        {
            return [AccountSystemKey::AccountsReceivable, AccountSystemKey::AccountsPayable];
        }

        public function getAccountIdentifier(): array
        {
            return ['id' => 777, 'name' => 'Dual Role Entity'];
        }
    };

    $arAccount = $this->resolver->resolveEntityAccount($entity, AccountSystemKey::CurrentAssets);
    $apAccount = $this->resolver->resolveEntityAccount($entity, AccountSystemKey::CurrentLiabilities);

    expect($arAccount->getKey())->not->toBe($apAccount->getKey());
    expect($arAccount->name)->toBe('Dual Role Entity');
    expect($apAccount->name)->toBe('Dual Role Entity');
    expect($arAccount->parent->system_key)->toBe(AccountSystemKey::CurrentAssets);
    expect($apAccount->parent->system_key)->toBe(AccountSystemKey::CurrentLiabilities);
});
