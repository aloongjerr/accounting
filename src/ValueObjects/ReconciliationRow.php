<?php

namespace AloongJerr\Accounting\ValueObjects;

/**
 * Represents a single row in the Bank Reconciliation Report.
 *
 * Each row can be one of three types:
 * - 'matched': A bank statement line matched with a system journal entry
 * - 'unmatched_bank': A bank statement line with no matching system entry
 * - 'unmatched_system': A system journal entry with no matching bank statement line
 */
class ReconciliationRow
{
    public function __construct(
        public readonly string $type, // 'matched', 'unmatched_bank', 'unmatched_system'
        public readonly int $statementLineId,
        public readonly ?int $journalEntryId,
        public readonly string $date,
        public readonly string $description,
        public readonly string $reference,
        public readonly int $amount,
        public readonly string $bankType, // 'debit' or 'credit'
    ) {}
}
