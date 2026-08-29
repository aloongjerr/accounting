<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Events\JournalPaidEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Builder for "paid" transactions — money going out.
 *
 * Default mapping:
 *   Debit:  Entity AP account (via to()) or specified account
 *   Credit: Cash/Bank (system account)
 *
 * Usage:
 *   Accounting::paid(300000, 'Office supplies')
 *       ->to($supplier)       // Debit: supplier AP account
 *       ->fromCash()          // Credit: Cash on Hand
 *       ->commit();
 */
class PaidTransaction extends BaseTransaction
{
    protected ?Accountable $toEntity = null;

    protected ?Account $toAccount = null;

    protected AccountSystemKey $fromSystemKey = AccountSystemKey::CashOnHand;

    protected ?Account $fromAccount = null;

    /**
     * Set the target entity (supplier) — resolves to AP account for debit.
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
     * Credit from Cash on Hand (default).
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
     * Credit from a specific account.
     */
    public function from(Account $account): static
    {
        $this->fromAccount = $account;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalPaidEvent::class;
    }

    public function resolveEntries(): array
    {
        // Resolve debit account (left side — what is being paid)
        if ($this->toEntity) {
            $debitAccount = $this->resolver->resolveEntityAccount(
                $this->toEntity,
                AccountSystemKey::AccountsPayable,
                $this->tenantId,
                $this->data,
            );
        } elseif ($this->toAccount) {
            $debitAccount = $this->toAccount;
        } else {
            throw new \LogicException(
                'Paid transaction requires a target. Call to($entity) or toAccount($account).'
            );
        }

        // Resolve credit account (right side — where money comes from)
        $creditAccount = $this->fromAccount
            ?? $this->resolver->resolveSystemAccount($this->fromSystemKey, $this->tenantId);

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
