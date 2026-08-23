<?php

namespace AloongJerr\Accounting\Database\Seeders;

use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /** @var array<string, Account> */
    private array $accounts = [];

    public function run(): void
    {
        $enums = config('accounting.account_keys', []);

        foreach ($enums as $enumClass) {
            $this->validateEnum($enumClass);
            $this->seedEnum($enumClass);
        }
    }

    /**
     * Validate that the enum class implements HasAccountIdentity.
     */
    private function validateEnum(string $enumClass): void
    {
        if (! is_subclass_of($enumClass, HasAccountIdentity::class)) {
            throw new \InvalidArgumentException(
                "Account key enum [{$enumClass}] must implement [" . HasAccountIdentity::class . '].'
            );
        }
    }

    /**
     * Seed all accounts from a single enum.
     */
    private function seedEnum(string $enumClass): void
    {
        $cases = $enumClass::cases();

        // Level 0: Groups (parentKey returns null)
        $groups = array_filter($cases, fn ($case) => $case->parentKey() === null);

        foreach ($groups as $key) {
            $this->accounts[$key->value] = Account::query()->create([
                'name' => $key->getLabel(),
                'code' => $key->getCode(),
                'type' => AccountType::Group,
                'system_key' => $key,
                'parent_id' => null,
            ]);
        }

        // Level 1: Categories (parent is a group)
        $categories = array_filter($cases, function ($case) {
            $parent = $case->parentKey();

            return $parent !== null && $parent->parentKey() === null;
        });

        foreach ($categories as $key) {
            $parentKey = $key->parentKey();
            $this->accounts[$key->value] = Account::query()->create([
                'name' => $key->getLabel(),
                'code' => $key->getCode(),
                'type' => AccountType::Category,
                'system_key' => $key,
                'parent_id' => $this->accounts[$parentKey->value]->getKey(),
            ]);
        }

        // Level 2+: Accounts (parent is a category or deeper)
        $leafAccounts = array_filter($cases, function ($case) {
            $parent = $case->parentKey();

            return $parent !== null && $parent->parentKey() !== null;
        });

        foreach ($leafAccounts as $key) {
            $parentKey = $key->parentKey();
            $this->accounts[$key->value] = Account::query()->create([
                'name' => $key->getLabel(),
                'code' => $key->getCode(),
                'type' => AccountType::Account,
                'system_key' => $key,
                'parent_id' => $this->accounts[$parentKey->value]->getKey(),
            ]);
        }
    }
}
