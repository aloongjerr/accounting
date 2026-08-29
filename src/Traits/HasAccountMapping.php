<?php

namespace AloongJerr\Accounting\Traits;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use BackedEnum;

/**
 * Convenience trait for models implementing the Accountable interface.
 *
 * Provides default implementations for getAccountKeys() and getAccountIdentifier().
 * The host model only needs to define getAccountSystemKeys() to declare which
 * system account(s) it maps to.
 *
 * Usage:
 *   class Customer extends Model implements Accountable
 *   {
 *       use HasAccountMapping;
 *
 *       protected function getAccountSystemKeys(): AccountSystemKey|array|BackedEnum
 *       {
 *           return AccountSystemKey::AccountsReceivable;
 *       }
 *   }
 *
 * Customize the account display name by overriding getAccountName():
 *   protected function getAccountName(): string
 *   {
 *       return $this->company_name; // instead of default $this->name
 *   }
 */
trait HasAccountMapping
{
    /**
     * Get the account system key(s) this model maps to.
     *
     * Must be overridden by the host model to declare which system account(s)
     * it represents. Return a single key or an array for dual-role entities
     * (e.g., a supplier that is also a customer).
     *
     * @return AccountSystemKey|BackedEnum|array<AccountSystemKey|BackedEnum>
     */
    abstract protected function getAccountSystemKeys(): AccountSystemKey|BackedEnum|array;

    /**
     * Get the account system key(s) — implements Accountable interface.
     *
     * @param  array<string, mixed>  $data  Additional context data from the transaction
     * @return AccountSystemKey|BackedEnum|array<AccountSystemKey|BackedEnum>
     */
    public function getAccountKeys(array $data = []): BackedEnum|array|AccountSystemKey
    {
        return $this->getAccountSystemKeys();
    }

    /**
     * Get the unique identifier for the individual account record.
     *
     * Uses the model's primary key and the account name.
     * Override getAccountName() to customize the display name.
     *
     * @param  array<string, mixed>  $data  Additional context data from the transaction
     * @return array{id: int|string, name: string}
     */
    public function getAccountIdentifier(array $data = []): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->getAccountName(),
        ];
    }

    /**
     * Get the display name for the individual account record.
     *
     * Defaults to the model's 'name' attribute. Override this method
     * to use a different attribute or computed value.
     */
    protected function getAccountName(): string
    {
        return $this->getAttribute('name') ?? (string) $this->getKey();
    }
}
