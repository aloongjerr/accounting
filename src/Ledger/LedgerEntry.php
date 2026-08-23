<?php

namespace AloongJerr\Accounting\Ledger;

use AloongJerr\Accounting\Models\JournalEntry;
use Carbon\Carbon;

/**
 * Represents a single entry in an account ledger.
 *
 * @property Carbon $date
 * @property string $description
 * @property int $debit
 * @property int $credit
 * @property int $runningBalance
 * @property int $journalId
 */
class LedgerEntry
{
    public function __construct(
        public readonly Carbon $date,
        public readonly string $description,
        public readonly int $debit,
        public readonly int $credit,
        public readonly int $runningBalance,
        public readonly int $journalId,
    ) {}

    /**
     * Create from a database result row or Eloquent JournalEntry model.
     *
     * Accepts either:
     * - A flat stdClass/row object with {date, description, debit, credit, journal_id}
     * - A JournalEntry Eloquent model with loaded 'journal' relation
     */
    public static function fromRow(object $row, int $runningBalance = 0): self
    {
        if ($row instanceof JournalEntry) {
            return new self(
                date: $row->journal->date instanceof Carbon ? $row->journal->date : Carbon::parse($row->journal->date),
                description: $row->journal->description ?? '',
                debit: $row->debit,
                credit: $row->credit,
                runningBalance: $runningBalance,
                journalId: $row->journal_id,
            );
        }

        return new self(
            date: Carbon::parse($row->date),
            description: $row->description ?? '',
            debit: (int) $row->debit,
            credit: (int) $row->credit,
            runningBalance: $runningBalance,
            journalId: (int) $row->journal_id,
        );
    }
}
