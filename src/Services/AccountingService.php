<?php

namespace AloongJerr\Accounting\Services;

use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Transactions\AdjustmentTransaction;
use AloongJerr\Accounting\Transactions\ManualJournal;
use AloongJerr\Accounting\Transactions\PaidTransaction;
use AloongJerr\Accounting\Transactions\PurchasedTransaction;
use AloongJerr\Accounting\Transactions\ReceivedTransaction;
use AloongJerr\Accounting\Transactions\SoldTransaction;

/**
 * Main accounting service.
 *
 * Entry point for all journal transactions. Bound in the container
 * and accessed via the Accounting facade.
 *
 * Named types (convenience):
 *   Accounting::received(500000, 'Payment')->from($customer)->toCash()->commit();
 *   Accounting::paid(300000, 'Supplies')->to($supplier)->fromCash()->commit();
 *   Accounting::sold(150000, 'Sale')->to($customer)->commit();
 *   Accounting::purchased(200000, 'Equipment')->forExpense($key)->from($supplier)->commit();
 *   Accounting::adjustment(50000, 'Correction')->debit($a)->credit($b)->commit();
 *
 * Generic (escape hatch):
 *   Accounting::journal('Contra offset')->debit($ap, 500000)->credit($ar, 500000)->commit();
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
     */
    public function received(int $amount, string $description = ''): ReceivedTransaction
    {
        return new ReceivedTransaction($amount, $description, $this->resolver);
    }

    /**
     * Create a paid transaction (money going out).
     */
    public function paid(int $amount, string $description = ''): PaidTransaction
    {
        return new PaidTransaction($amount, $description, $this->resolver);
    }

    /**
     * Create a sold transaction (sale made).
     */
    public function sold(int $amount, string $description = ''): SoldTransaction
    {
        return new SoldTransaction($amount, $description, $this->resolver);
    }

    /**
     * Create a purchased transaction (purchase made).
     */
    public function purchased(int $amount, string $description = ''): PurchasedTransaction
    {
        return new PurchasedTransaction($amount, $description, $this->resolver);
    }

    /**
     * Create an adjustment transaction.
     */
    public function adjustment(int $amount, string $description = ''): AdjustmentTransaction
    {
        return new AdjustmentTransaction($amount, $description, $this->resolver);
    }
}
