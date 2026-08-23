<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\JournalEntry;

beforeEach(function () {
    $this->group = Account::query()->create([
        'name' => 'Assets',
        'code' => '1000',
        'type' => AccountType::Group,
        'system_key' => AccountSystemKey::Assets,
        'is_active' => true,
    ]);

    $this->category = Account::query()->create([
        'name' => 'Current Assets',
        'code' => '1100',
        'type' => AccountType::Category,
        'system_key' => AccountSystemKey::CurrentAssets,
        'parent_id' => $this->group->getKey(),
        'is_active' => true,
    ]);

    $this->account = Account::query()->create([
        'name' => 'Cash on Hand',
        'code' => '1101',
        'type' => AccountType::Account,
        'system_key' => AccountSystemKey::CashOnHand,
        'parent_id' => $this->category->getKey(),
        'is_active' => true,
    ]);
});

it('has correct fillable attributes', function () {
    $account = Account::query()->create([
        'name' => 'Test Account',
        'code' => '9999',
        'type' => AccountType::Account,
        'is_active' => true,
    ]);

    expect($account->name)->toBe('Test Account');
    expect($account->code)->toBe('9999');
    expect($account->type)->toBe(AccountType::Account);
    expect($account->is_active)->toBeTrue();
});

it('casts type to AccountType enum', function () {
    $account = Account::find($this->account->getKey());

    expect($account->type)->toBeInstanceOf(AccountType::class);
    expect($account->type)->toBe(AccountType::Account);
});

it('casts system_key to AccountSystemKey enum', function () {
    $account = Account::find($this->account->getKey());

    expect($account->system_key)->toBeInstanceOf(AccountSystemKey::class);
    expect($account->system_key)->toBe(AccountSystemKey::CashOnHand);
});

it('has parent relationship', function () {
    expect($this->category->parent)->toBeInstanceOf(Account::class);
    expect($this->category->parent->getKey())->toBe($this->group->getKey());
});

it('has children relationship', function () {
    expect($this->group->children)->toHaveCount(1);
    expect($this->group->children->first()->getKey())->toBe($this->category->getKey());
});

it('group has no parent', function () {
    expect($this->group->parent)->toBeNull();
});

it('identifies leaf accounts', function () {
    expect($this->account->isLeaf())->toBeTrue();
    expect($this->category->isLeaf())->toBeFalse();
    expect($this->group->isLeaf())->toBeFalse();
});

it('identifies groups', function () {
    expect($this->group->isGroup())->toBeTrue();
    expect($this->category->isGroup())->toBeFalse();
    expect($this->account->isGroup())->toBeFalse();
});

it('identifies categories', function () {
    expect($this->category->isCategory())->toBeTrue();
    expect($this->group->isCategory())->toBeFalse();
    expect($this->account->isCategory())->toBeFalse();
});

it('gets ancestors', function () {
    $ancestors = $this->account->getAncestors();

    expect($ancestors)->toHaveCount(2);
    expect($ancestors[0]->getKey())->toBe($this->group->getKey());
    expect($ancestors[1]->getKey())->toBe($this->category->getKey());
});

it('gets descendants', function () {
    $descendants = $this->group->getDescendants();

    expect($descendants)->toHaveCount(2);
});

it('gets leaf accounts from group', function () {
    $leaves = $this->group->getLeafAccounts();

    expect($leaves)->toHaveCount(1);
    expect($leaves[0]->getKey())->toBe($this->account->getKey());
});

it('returns itself when getLeafAccounts called on leaf', function () {
    $leaves = $this->account->getLeafAccounts();

    expect($leaves)->toHaveCount(1);
    expect($leaves[0]->getKey())->toBe($this->account->getKey());
});

it('scopes to active accounts', function () {
    $inactive = Account::query()->create([
        'name' => 'Inactive',
        'code' => '9998',
        'type' => AccountType::Account,
        'is_active' => false,
    ]);

    $active = Account::query()->active()->get();

    expect($active)->not->toContain($inactive);
    expect($active)->toHaveCount(3);
});

it('scopes to type', function () {
    $groups = Account::query()->ofType(AccountType::Group)->get();

    expect($groups)->toHaveCount(1);
    expect($groups->first()->getKey())->toBe($this->group->getKey());
});

it('scopes to system key', function () {
    $account = Account::query()->systemKey(AccountSystemKey::CashOnHand)->first();

    expect($account)->not->toBeNull();
    expect($account->getKey())->toBe($this->account->getKey());
});

it('scopes to leaf accounts only', function () {
    $leaves = Account::query()->leaf()->get();

    expect($leaves)->toHaveCount(1);
    expect($leaves->first()->getKey())->toBe($this->account->getKey());
});

it('has journal entries relationship', function () {
    expect($this->account->journalEntries())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('has morphable model relationship', function () {
    expect($this->account->model())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('calculates balance as zero with no entries', function () {
    expect($this->account->getBalance())->toBe(0.0);
});
