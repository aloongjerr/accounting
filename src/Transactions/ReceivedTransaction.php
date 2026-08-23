<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Events\JournalReceivedEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Builder for "received" transactions — money coming in.
 *
 * Default mapping:
 *   Debit:  Cash/Bank (system account)
 *   Credit: Entity AR account (via from()) or specified account
 *
 * Usage:
 *   Accounting::received(500000, 'Invoice payment')
 *       ->from($customer)       // Credit: customer AR account
 *       ->toCash()              // Debit: Cash on Hand
 *       ->commit();
 */
class ReceivedTransaction extends BaseTransaction
{
    protected ?Accountable $fromEntity = null;

    protected ?Account $fromAccount = null;

    protected AccountSystemKey $toSystemKey = AccountSystemKey::CashOnHand;

    protected ?Account $toAccount = null;

    /**
     * Set the source entity (customer) — resolves to AR account for credit.
     */
    public function from(Accountable $entity): static
    {
        $this->fromEntity = $entity;

        return $this;
    }

    /**
     * Set a specific account for the credit side.
     */
    public function fromAccount(Account $account): static
    {
        $this->fromAccount = $account;

        return $this;
    }

    /**
     * Debit to Cash on Hand (default).
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
     * Debit to a specific account.
     */
    public function to(Account $account): static
    {
        $this->toAccount = $account;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalReceivedEvent::class;
    }

    public function resolveEntries(): array
    {
        // Resolve debit account (left side — where money goes)
        $debitAccount = $this->toAccount
            ?? $this->resolver->resolveSystemAccount($this->toSystemKey, $this->tenantId);

        // Resolve credit account (right side — where money comes from)
        if ($this->fromEntity) {
            $creditAccount = $this->resolver->resolveEntityAccount(
                $this->fromEntity,
                AccountSystemKey::AccountsReceivable,
                $this->tenantId,
            );
        } elseif ($this->fromAccount) {
            $creditAccount = $this->fromAccount;
        } else {
            throw new \LogicException(
                'Received transaction requires a source. Call from($entity) or fromAccount($account).'
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
