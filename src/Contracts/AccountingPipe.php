<?php

namespace AloongJerr\Accounting\Contracts;

use Closure;

/**
 * Contract for transaction pipes that run before journal commit.
 *
 * Pipes follow the Laravel middleware pattern and are executed
 * in sequence via the pipeThrough() method on the transaction builder.
 */
interface AccountingPipe
{
    /**
     * Handle the transaction before commit.
     *
     * @param  mixed  $transaction  The transaction builder instance
     * @param  Closure  $next  Call the next pipe in the pipeline
     * @return mixed
     */
    public function handle(mixed $transaction, Closure $next): mixed;
}
