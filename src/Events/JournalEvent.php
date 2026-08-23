<?php

namespace AloongJerr\Accounting\Events;

use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Transactions\BaseTransaction;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base event for all journal transactions.
 *
 * Fired after a successful commit(). Contains the journal model
 * and the transaction builder for full context.
 */
abstract class JournalEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Journal $journal,
        public readonly BaseTransaction $transaction,
    ) {}
}
