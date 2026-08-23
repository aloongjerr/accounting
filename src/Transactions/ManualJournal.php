<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Events\JournalManualEvent;
use AloongJerr\Accounting\Models\Account;

/**
 * Generic journal builder for custom debit/credit combinations.
 *
 * Used as an escape hatch when named transaction types
 * (received, paid, sold, etc.) don't cover the scenario.
 *
 * Usage:
 *   Accounting::journal('Contra offset')
 *       ->debit($apAccount, 5000)
 *       ->credit($arAccount, 5000)
 *       ->commit();
 */
class ManualJournal extends BaseTransaction
{
    /** @var array<int, array{account_id: int, debit: int, credit: int}> */
    protected array $manualEntries = [];

    protected int $totalAmount = 0;

    public function __construct(string $description = '')
    {
        parent::__construct(0, $description);
    }

    /**
     * Add a debit entry.
     */
    public function debit(Account $account, int $amount): static
    {
        $this->manualEntries[] = [
            'account_id' => $account->getKey(),
            'debit' => $amount,
            'credit' => 0,
        ];
        $this->totalAmount += $amount;

        return $this;
    }

    /**
     * Add a credit entry.
     */
    public function credit(Account $account, int $amount): static
    {
        $this->manualEntries[] = [
            'account_id' => $account->getKey(),
            'debit' => 0,
            'credit' => $amount,
        ];
        $this->totalAmount += $amount;

        return $this;
    }

    public function getEventClass(): string
    {
        return JournalManualEvent::class;
    }

    public function resolveEntries(): array
    {
        return $this->manualEntries;
    }

    public function getAmount(): int
    {
        return $this->totalAmount;
    }
}
