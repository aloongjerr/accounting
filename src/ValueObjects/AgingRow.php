<?php

namespace AloongJerr\Accounting\ValueObjects;

/**
 * Represents a single aging row in the AR/AP Aging Report.
 *
 * Each row corresponds to a journal entry that affects an AR/AP account,
 * showing the outstanding amount and how many days old it is.
 *
 * @property int $accountId
 * @property string $accountName
 * @property int $journalId
 * @property \Carbon\Carbon $journalDate
 * @property string|null $description
 * @property int $amount  Outstanding amount in cents (always positive)
 * @property int $daysOld Number of days since the journal date
 * @property string $bucket  Age bucket label (e.g., 'current', '31-60')
 */
class AgingRow
{
    public function __construct(
        public readonly int $accountId,
        public readonly string $accountName,
        public readonly int $journalId,
        public readonly \Carbon\Carbon $journalDate,
        public readonly ?string $description,
        public readonly int $amount,
        public readonly int $daysOld,
        public readonly string $bucket,
    ) {}
}
