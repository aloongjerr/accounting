<?php

namespace AloongJerr\Accounting\Services;

use AloongJerr\Accounting\AccountingConfiguration;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Ledger\AccountLedger;
use AloongJerr\Accounting\Ledger\TAccount;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Reports\BalanceSheet;
use AloongJerr\Accounting\Reports\IncomeStatement;
use AloongJerr\Accounting\Reports\TrialBalance;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Snapshots\SnapshotManager;
use AloongJerr\Accounting\Transactions\AdjustmentTransaction;
use AloongJerr\Accounting\Transactions\ManualJournal;
use AloongJerr\Accounting\Transactions\PaidTransaction;
use AloongJerr\Accounting\Transactions\PurchasedTransaction;
use AloongJerr\Accounting\Transactions\ReceivedTransaction;
use AloongJerr\Accounting\Transactions\SoldTransaction;
use AloongJerr\Accounting\Transactions\TransferTransaction;
use BackedEnum;
use Closure;

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
 *   Accounting::transfer(500000, 'Cash to bank')->fromCash()->toBank()->commit();
 *   Accounting::adjustment(50000, 'Correction')->debit($a)->credit($b)->commit();
 *
 * Generic (escape hatch):
 *   Accounting::journal('Contra offset')->debit($ap, 500000)->credit($ar, 500000)->commit();
 */
class AccountingService
{
    public function __construct(
        protected AccountResolver $resolver,
        protected BalanceService $balanceService,
        protected SnapshotManager $snapshotManager,
    ) {}

    /**
     * Configure the accounting package using a fluent interface.
     *
     * @param Closure(AccountingConfiguration): void $callback
     */
    public function configure(Closure $callback): void
    {
        $config = app(AccountingConfiguration::class);
        $callback($config);
        $config->apply();
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
     * Create a transfer transaction (move money between own accounts).
     */
    public function transfer(int $amount, string $description = ''): TransferTransaction
    {
        return new TransferTransaction($amount, $description, $this->resolver);
    }

    /**
     * Create an adjustment transaction.
     */
    public function adjustment(int $amount, string $description = ''): AdjustmentTransaction
    {
        return new AdjustmentTransaction($amount, $description, $this->resolver);
    }

    // ── Ledger ──

    /**
     * Get the ledger for a specific account.
     *
     * @param  AccountSystemKey|BackedEnum|Account  $account
     */
    public function ledger(AccountSystemKey|BackedEnum|Account $account): AccountLedger
    {
        $account = $this->resolveAccount($account);

        return new AccountLedger($account);
    }

    /**
     * Get the T-account view for a specific account.
     *
     * @param  AccountSystemKey|BackedEnum|Account  $account
     */
    public function tAccount(AccountSystemKey|BackedEnum|Account $account): TAccount
    {
        $account = $this->resolveAccount($account);

        return new TAccount($account);
    }

    // ── Reports ──

    /**
     * Create a trial balance report.
     */
    public function trialBalance(): TrialBalance
    {
        return new TrialBalance();
    }

    /**
     * Create an income statement report.
     */
    public function incomeStatement(): IncomeStatement
    {
        return new IncomeStatement();
    }

    /**
     * Create a balance sheet report.
     */
    public function balanceSheet(): BalanceSheet
    {
        return new BalanceSheet();
    }

    // ── Snapshot ──

    /**
     * Get the snapshot manager.
     */
    public function snapshot(): SnapshotManager
    {
        return $this->snapshotManager;
    }

    /**
     * Get the balance service.
     */
    public function balanceService(): BalanceService
    {
        return $this->balanceService;
    }

    // ── Helpers ──

    /**
     * Resolve an account from a system key or Account model.
     */
    protected function resolveAccount(AccountSystemKey|BackedEnum|Account $account): Account
    {
        if ($account instanceof Account) {
            return $account;
        }

        return $this->resolver->resolveSystemAccount($account);
    }
}
