<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Events\JournalPurchasedEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Builder for "purchased" transactions — purchase made.
 *
 * Default mapping:
 *   Debit:  Expense/Asset (system account)
 *   Credit: Entity AP account (via from()) or Cash/Bank
 *
 * Usage:
 *   Accounting::purchased(200000, 'Office equipment')
 *       ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
 *       ->from($supplier)       // Credit: supplier AP account
 *       ->commit();
 */
class PurchasedTransaction extends BaseTransaction
{
    protected ?Accountable $fromEntity = null;

    protected ?Account $fromAccount = null;

    protected bool $forCash = false;

    protected bool $forBank = false;

    protected AccountSystemKey $expenseKey = AccountSystemKey::CostOfRevenue;

    protected ?Account $expenseAccount = null;

    /**
     * Set the supplier entity — resolves to AP account for credit.
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
     * Credit from Cash on Hand (cash purchase).
     */
    public function forCash(): static
    {
        $this->forCash = true;
        $this->forBank = false;

        return $this;
    }

    /**
     * Credit from Cash in Bank (cash purchase via bank).
     */
    public function forBank(): static
    {
        $this->forBank = true;
        $this->forCash = false;

        return $this;
    }

    /**
     * Set the expense/asset account (default: CostOfRevenue).
     */
    public function forExpense(AccountSystemKey $key): static
    {
        $this->expenseKey = $key;
        $this->expenseAccount = null;

        return $this;
    }

    /**
     * Set a specific expense/asset account.
     */
    public function forExpenseAccount(Account $account): static
    {
        $this->expenseAccount = $account;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalPurchasedEvent::class;
    }

    public function resolveEntries(): array
    {
        // Resolve debit account (left side — what was purchased)
        $debitAccount = $this->expenseAccount
            ?? $this->resolver->resolveSystemAccount($this->expenseKey, $this->tenantId);

        // Resolve credit account (right side — who/what is paying)
        if ($this->forCash || $this->forBank) {
            $creditKey = $this->forCash
                ? AccountSystemKey::CashOnHand
                : AccountSystemKey::CashInBank;
            $creditAccount = $this->resolver->resolveSystemAccount($creditKey, $this->tenantId);
        } elseif ($this->fromEntity) {
            $creditAccount = $this->resolver->resolveEntityAccount(
                $this->fromEntity,
                AccountSystemKey::AccountsPayable,
                $this->tenantId,
                $this->data,
            );
        } elseif ($this->fromAccount) {
            $creditAccount = $this->fromAccount;
        } else {
            throw new \LogicException(
                'Purchased transaction requires a payment source. Call from($entity), fromAccount($account), forCash(), or forBank().'
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
