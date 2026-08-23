<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Events\JournalSoldEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Builder for "sold" transactions — sale made.
 *
 * Default mapping:
 *   Debit:  Entity AR account (via to()) or Cash/Bank
 *   Credit: Revenue (system account)
 *
 * Usage:
 *   Accounting::sold(150000, 'Product sale')
 *       ->to($customer)       // Debit: customer AR account
 *       ->forCash()           // OR use ->to($customer) for credit sale
 *       ->commit();
 */
class SoldTransaction extends BaseTransaction
{
    protected ?Accountable $toEntity = null;

    protected ?Account $toAccount = null;

    protected bool $forCash = false;

    protected bool $forBank = false;

    protected AccountSystemKey $revenueKey = AccountSystemKey::SalesRevenue;

    protected ?Account $revenueAccount = null;

    /**
     * Set the customer entity — resolves to AR account for debit.
     */
    public function to(Accountable $entity): static
    {
        $this->toEntity = $entity;

        return $this;
    }

    /**
     * Set a specific account for the debit side.
     */
    public function toAccount(Account $account): static
    {
        $this->toAccount = $account;

        return $this;
    }

    /**
     * Debit to Cash on Hand (cash sale).
     */
    public function forCash(): static
    {
        $this->forCash = true;
        $this->forBank = false;

        return $this;
    }

    /**
     * Debit to Cash in Bank (cash sale via bank).
     */
    public function forBank(): static
    {
        $this->forBank = true;
        $this->forCash = false;

        return $this;
    }

    /**
     * Set the revenue account (default: SalesRevenue).
     */
    public function forRevenue(AccountSystemKey $key): static
    {
        $this->revenueKey = $key;
        $this->revenueAccount = null;

        return $this;
    }

    /**
     * Set a specific revenue account.
     */
    public function forRevenueAccount(Account $account): static
    {
        $this->revenueAccount = $account;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalSoldEvent::class;
    }

    public function resolveEntries(): array
    {
        // Resolve debit account (left side — who owes us / where cash goes)
        if ($this->forCash || $this->forBank) {
            $debitKey = $this->forCash
                ? AccountSystemKey::CashOnHand
                : AccountSystemKey::CashInBank;
            $debitAccount = $this->resolver->resolveSystemAccount($debitKey, $this->tenantId);
        } elseif ($this->toEntity) {
            $debitAccount = $this->resolver->resolveEntityAccount(
                $this->toEntity,
                AccountSystemKey::AccountsReceivable,
                $this->tenantId,
            );
        } elseif ($this->toAccount) {
            $debitAccount = $this->toAccount;
        } else {
            throw new \LogicException(
                'Sold transaction requires a target. Call to($entity), toAccount($account), forCash(), or forBank().'
            );
        }

        // Resolve credit account (right side — revenue)
        $creditAccount = $this->revenueAccount
            ?? $this->resolver->resolveSystemAccount($this->revenueKey, $this->tenantId);

        return [
            [
                'account_id' => $debitAccount->getKey(),
                'debit' => $this->amount,
                'credit' => 0,
                'description' => $this->description,
            ],
            [
                'account_id' => $creditAccount->getKey(),
                'debit' => 0,
                'credit' => $this->amount,
                'description' => $this->description,
            ],
        ];
    }
}
