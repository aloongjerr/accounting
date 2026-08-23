<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Events\JournalAdjustmentEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Builder for "adjustment" transactions — manual corrections.
 *
 * Both debit and credit accounts must be explicitly specified.
 *
 * Usage:
 *   Accounting::adjustment(50000, 'Inventory correction')
 *       ->debit($inventoryAccount)
 *       ->credit($expenseAccount)
 *       ->commit();
 */
class AdjustmentTransaction extends BaseTransaction
{
    protected ?Account $debitAccount = null;

    protected ?Account $creditAccount = null;

    /**
     * Set the debit account.
     */
    public function debit(Account $account): static
    {
        $this->debitAccount = $account;

        return $this;
    }

    /**
     * Set the credit account.
     */
    public function credit(Account $account): static
    {
        $this->creditAccount = $account;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalAdjustmentEvent::class;
    }

    public function resolveEntries(): array
    {
        if (! $this->debitAccount) {
            throw new \LogicException(
                'Adjustment transaction requires a debit account. Call debit($account).'
            );
        }

        if (! $this->creditAccount) {
            throw new \LogicException(
                'Adjustment transaction requires a credit account. Call credit($account).'
            );
        }

        return [
            [
                'account_id' => $this->debitAccount->getKey(),
                'debit' => $this->amount,
                'credit' => 0,
                'description' => $this->description,
            ],
            [
                'account_id' => $this->creditAccount->getKey(),
                'debit' => 0,
                'credit' => $this->amount,
                'description' => $this->description,
            ],
        ];
    }
}
