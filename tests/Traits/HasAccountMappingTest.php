<?php

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Traits\HasAccountMapping;
use Illuminate\Database\Eloquent\Model;

// ── Test Models ──

class TestCustomer extends Model implements Accountable
{
    use HasAccountMapping;

    protected $guarded = [];
    public $timestamps = false;

    protected function getAccountSystemKeys(): AccountSystemKey|array|\BackedEnum
    {
        return AccountSystemKey::AccountsReceivable;
    }
}

class TestSupplier extends Model implements Accountable
{
    use HasAccountMapping;

    protected $guarded = [];
    public $timestamps = false;

    protected function getAccountSystemKeys(): AccountSystemKey|array|\BackedEnum
    {
        return AccountSystemKey::AccountsPayable;
    }
}

class TestDualRoleEntity extends Model implements Accountable
{
    use HasAccountMapping;

    protected $guarded = [];
    public $timestamps = false;

    protected function getAccountSystemKeys(): AccountSystemKey|array|\BackedEnum
    {
        return [AccountSystemKey::AccountsReceivable, AccountSystemKey::AccountsPayable];
    }
}

class TestCustomNameEntity extends Model implements Accountable
{
    use HasAccountMapping;

    protected $guarded = [];
    public $timestamps = false;

    protected function getAccountSystemKeys(): AccountSystemKey|array|\BackedEnum
    {
        return AccountSystemKey::AccountsReceivable;
    }

    protected function getAccountName(): string
    {
        return $this->getAttribute('company_name') ?? 'Unknown';
    }
}

class TestNoNameAttributeEntity extends Model implements Accountable
{
    use HasAccountMapping;

    protected $guarded = [];
    public $timestamps = false;

    protected function getAccountSystemKeys(): AccountSystemKey|array|\BackedEnum
    {
        return AccountSystemKey::AccountsReceivable;
    }
}

// ── Tests ──

it('returns correct system key via getAccountKeys', function () {
    $customer = new TestCustomer(['id' => 1, 'name' => 'Acme Corp']);

    expect($customer->getAccountKeys())->toBe(AccountSystemKey::AccountsReceivable);
});

it('returns correct system key for supplier', function () {
    $supplier = new TestSupplier(['id' => 1, 'name' => 'Supply Co']);

    expect($supplier->getAccountKeys())->toBe(AccountSystemKey::AccountsPayable);
});

it('returns array of system keys for dual-role entity', function () {
    $entity = new TestDualRoleEntity(['id' => 1, 'name' => 'Dual Corp']);

    $keys = $entity->getAccountKeys();

    expect($keys)->toBeArray();
    expect($keys)->toHaveCount(2);
    expect($keys)->toContain(AccountSystemKey::AccountsReceivable);
    expect($keys)->toContain(AccountSystemKey::AccountsPayable);
});

it('returns identifier with model key and name', function () {
    $customer = new TestCustomer(['id' => 42, 'name' => 'Acme Corp']);

    $identifier = $customer->getAccountIdentifier();

    expect($identifier)->toBe(['id' => 42, 'name' => 'Acme Corp']);
});

it('uses custom account name when overridden', function () {
    $entity = new TestCustomNameEntity(['id' => 10, 'company_name' => 'Custom Name Inc']);

    $identifier = $entity->getAccountIdentifier();

    expect($identifier['name'])->toBe('Custom Name Inc');
    expect($identifier['id'])->toBe(10);
});

it('falls back to key when name attribute is missing', function () {
    $entity = new TestNoNameAttributeEntity(['id' => 99]);

    $identifier = $entity->getAccountIdentifier();

    expect($identifier['id'])->toBe(99);
    expect($identifier['name'])->toBe('99');
});

it('accepts data parameter without error', function () {
    $customer = new TestCustomer(['id' => 1, 'name' => 'Acme Corp']);

    expect($customer->getAccountKeys(['invoice_id' => 123]))->toBe(AccountSystemKey::AccountsReceivable);
    expect($customer->getAccountIdentifier(['invoice_id' => 123]))->toBe(['id' => 1, 'name' => 'Acme Corp']);
});

it('implements Accountable interface', function () {
    $customer = new TestCustomer(['id' => 1, 'name' => 'Test']);

    expect($customer)->toBeInstanceOf(Accountable::class);
});

it('works with different model IDs', function () {
    $customer1 = new TestCustomer(['id' => 1, 'name' => 'Customer A']);
    $customer2 = new TestCustomer(['id' => 2, 'name' => 'Customer B']);

    expect($customer1->getAccountIdentifier()['id'])->toBe(1);
    expect($customer2->getAccountIdentifier()['id'])->toBe(2);
    expect($customer1->getAccountIdentifier()['name'])->toBe('Customer A');
    expect($customer2->getAccountIdentifier()['name'])->toBe('Customer B');
});
