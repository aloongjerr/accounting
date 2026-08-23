<?php

namespace AloongJerr\Accounting\ValueObjects;

/**
 * Represents the period activity (debits and credits) for a single account.
 *
 * Used by BalanceService and SnapshotDriver to return typed
 * objects instead of raw stdClass from aggregation queries.
 *
 * @property int $accountId
 * @property int $debit
 * @property int $credit
 * @property int $balance
 */
class AccountActivity
{
    public function __construct(
        public readonly int $accountId,
        public readonly int $debit,
        public readonly int $credit,
        public readonly int $balance,
    ) {}

    /**
     * Create from a raw query result object (stdClass).
     */
    public static function fromRow(object $row): self
    {
        return new self(
            accountId: (int) $row->account_id,
            debit: (int) ($row->debit ?? 0),
            credit: (int) ($row->credit ?? 0),
            balance: (int) (($row->debit ?? 0) - ($row->credit ?? 0)),
        );
    }

    /**
     * Create from individual values.
     */
    public static function make(int $accountId, int $debit, int $credit): self
    {
        return new self(
            accountId: $accountId,
            debit: $debit,
            credit: $credit,
            balance: $debit - $credit,
        );
    }

    /**
     * Map a collection of raw rows to AccountActivity objects.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function collectFromRows($rows): \Illuminate\Support\Collection
    {
        return $rows->map(fn (object $row) => self::fromRow($row));
    }
}
