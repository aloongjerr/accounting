<?php

namespace AloongJerr\Accounting\Resolvers;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Models\Account;

/**
 * Resolves accounts for journal entries.
 *
 * Handles two scenarios:
 * 1. System accounts — looked up by system_key (e.g., CashOnHand, AccountsReceivable)
 * 2. Entity accounts — find-or-create individual accounts linked to Accountable models
 */
class AccountResolver
{
    /**
     * Resolve a system account by its system key.
     *
     * Used for built-in accounts like Cash, Bank, etc.
     */
    public function resolveSystemAccount(AccountSystemKey $key, ?int $tenantId = null): Account
    {
        $query = Account::query()
            ->where('system_key', $key)
            ->where('type', AccountType::Account);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        $account = $query->first();

        if (! $account) {
            throw new \RuntimeException(
                "System account [{$key->value}] not found. Run the chart of accounts seeder first."
            );
        }

        return $account;
    }

    /**
     * Resolve an individual account for an Accountable entity.
     *
     * Finds existing account or creates a new one under the given parent key.
     * This handles the dual-role entity scenario — the same model can have
     * separate accounts under different parents (e.g., AR and AP).
     */
    public function resolveEntityAccount(
        Accountable $entity,
        AccountSystemKey $parentKey,
        ?int $tenantId = null,
    ): Account {
        $identifier = $entity->getAccountIdentifier();
        $modelType = get_class($entity);
        $modelId = $identifier['id'];

        // Find the parent account
        $parent = $this->resolveParentAccount($parentKey, $tenantId);

        // Look for existing individual account
        $account = Account::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('parent_id', $parent->getKey())
            ->first();

        if ($account) {
            return $account;
        }

        // Create new individual account
        return Account::query()->create([
            'name' => $identifier['name'],
            'type' => AccountType::Account,
            'parent_id' => $parent->getKey(),
            'model_type' => $modelType,
            'model_id' => $modelId,
            'tenant_id' => $tenantId,
            'currency' => $this->resolveCurrency($tenantId),
            'is_active' => true,
        ]);
    }

    /**
     * Resolve the parent account for a given system key.
     */
    public function resolveParentAccount(AccountSystemKey $parentKey, ?int $tenantId = null): Account
    {
        $query = Account::query()
            ->where('system_key', $parentKey);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        $parent = $query->first();

        if (! $parent) {
            throw new \RuntimeException(
                "Parent account [{$parentKey->value}] not found. Run the chart of accounts seeder first."
            );
        }

        return $parent;
    }

    /**
     * Resolve currency for the given company.
     */
    protected function resolveCurrency(?int $tenantId = null): string
    {
        if ($tenantId) {
            // Future: fetch from tenants table
        }

        return Accounting::config('currency', 'USD');
    }
}
