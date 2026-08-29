<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Events\JournalTransferredEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Builder for "transfer" transactions — moving money between own accounts.
 *
 * Default mapping:
 *   Debit:  Cash in Bank (system account)
 *   Credit: Cash on Hand (system account)
 *
 * Usage:
 *   Accounting::transfer(500000, 'Office cash top-up')
 *       ->fromCash()              // Credit: Cash on Hand
 *       ->toBank()                // Debit: Cash in Bank
 *       ->commit();
 *
 *   Accounting::transfer(1000000, 'Inter-bank transfer')
 *       ->fromBank()              // Credit: Cash in Bank (source)
 *       ->to($savingsAccount)     // Debit: Savings Account
 *       ->commit();
 */
class TransferTransaction extends BaseTransaction
{
    protected AccountSystemKey $fromSystemKey = AccountSystemKey::CashOnHand;

    protected ?Account $fromAccount = null;

    protected AccountSystemKey $toSystemKey = AccountSystemKey::CashInBank;

    protected ?Account $toAccount = null;

    /**
     * Credit from Cash on Hand (default source).
     */
    public function fromCash(): static
    {
        $this->fromSystemKey = AccountSystemKey::CashOnHand;
        $this->fromAccount = null;

        return $this;
    }

    /**
     * Credit from Cash in Bank.
     */
    public function fromBank(): static
    {
        $this->fromSystemKey = AccountSystemKey::CashInBank;
        $this->fromAccount = null;

        return $this;
    }

    /**
     * Credit from a specific system account key.
     */
    public function fromSystemKey(AccountSystemKey $key): static
    {
        $this->fromSystemKey = $key;
        $this->fromAccount = null;

        return $this;
    }

    /**
     * Credit from a specific account.
     */
    public function from(Account $account): static
    {
        $this->fromAccount = $account;

        return $this;
    }

    /**
     * Debit to Cash in Bank (default destination).
     */
    public function toCash(): static
    {
        $this->toSystemKey = AccountSystemKey::CashOnHand;
        $this->toAccount = null;

        return $this;
    }

    /**
     * Debit to Cash in Bank.
     */
    public function toBank(): static
    {
        $this->toSystemKey = AccountSystemKey::CashInBank;
        $this->toAccount = null;

        return $this;
    }

    /**
     * Debit to a specific system account key.
     */
    public function toSystemKey(AccountSystemKey $key): static
    {
        $this->toSystemKey = $key;
        $this->toAccount = null;

        return $this;
    }

    /**
     * Debit to a specific account.
     */
    public function to(Account $account): static
    {
        $this->toAccount = $account;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalTransferredEvent::class;
    }

    public function resolveEntries(): array
    {
        // Resolve debit account (left side — where money goes)
        $debitAccount = $this->toAccount
            ?? $this->resolver->resolveSystemAccount($this->toSystemKey, $this->tenantId);

        // Resolve credit account (right side — where money comes from)
        $creditAccount = $this->fromAccount
            ?? $this->resolver->resolveSystemAccount($this->fromSystemKey, $this->tenantId);

        // Prevent transferring to the same account
        if ($debitAccount->getKey() === $creditAccount->getKey()) {
            throw new \LogicException(
                'Cannot transfer to the same account. Source and destination must be different.'
            );
        }

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
