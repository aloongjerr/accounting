<?php

namespace AloongJerr\Accounting\Services;

use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Transactions\ManualJournal;

/**
 * Main accounting service.
 *
 * Entry point for all journal transactions. Bound in the container
 * and accessed via the Accounting facade.
 *
 * Named types (convenience):
 *   Accounting::received(500000, 'Payment')->from($customer)->toCash()->commit();
 *
 * Generic (escape hatch):
 *   Accounting::journal('Contra offset')->debit($ap, 500000)->credit($ar, 500000)->commit();
 *
 * Transaction type methods (received, paid, sold, etc.) are added
 * in Phase 3. This class provides the foundation.
 */
class AccountingService
{
    protected AccountResolver $resolver;

    public function __construct(AccountResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the account resolver instance.
     */
    public function resolver(): AccountResolver
    {
        return $this->resolver;
    }

    /**
     * Create a manual journal with explicit debit/credit entries.
     *
     * Escape hatch for transactions not covered by named types
     * (returns, refunds, contra, transfers, depreciation, etc.)
     */
    public function journal(string $description = ''): ManualJournal
    {
        return new ManualJournal($description);
    }

    /**
     * Create a received transaction (money coming in).
     *
     * Phase 3: Returns ReceivedTransaction builder.
     */
    public function received(int $amount, string $description = ''): mixed
    {
        // Phase 3: return new ReceivedTransaction($amount, $description, $this->resolver);
        throw new \BadMethodCallException('Transaction type [received] is not yet implemented. Coming in Phase 3.');
    }

    /**
     * Create a paid transaction (money going out).
     *
     * Phase 3: Returns PaidTransaction builder.
     */
    public function paid(int $amount, string $description = ''): mixed
    {
        // Phase 3: return new PaidTransaction($amount, $description, $this->resolver);
        throw new \BadMethodCallException('Transaction type [paid] is not yet implemented. Coming in Phase 3.');
    }

    /**
     * Create a sold transaction (sale made).
     *
     * Phase 3: Returns SoldTransaction builder.
     */
    public function sold(int $amount, string $description = ''): mixed
    {
        // Phase 3: return new SoldTransaction($amount, $description, $this->resolver);
        throw new \BadMethodCallException('Transaction type [sold] is not yet implemented. Coming in Phase 3.');
    }

    /**
     * Create a purchased transaction (purchase made).
     *
     * Phase 3: Returns PurchasedTransaction builder.
     */
    public function purchased(int $amount, string $description = ''): mixed
    {
        // Phase 3: return new PurchasedTransaction($amount, $description, $this->resolver);
        throw new \BadMethodCallException('Transaction type [purchased] is not yet implemented. Coming in Phase 3.');
    }

    /**
     * Create an adjustment transaction.
     *
     * Phase 3: Returns AdjustmentTransaction builder.
     */
    public function adjustment(int $amount, string $description = ''): mixed
    {
        // Phase 3: return new AdjustmentTransaction($amount, $description, $this->resolver);
        throw new \BadMethodCallException('Transaction type [adjustment] is not yet implemented. Coming in Phase 3.');
    }
}
