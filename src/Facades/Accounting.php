<?php

namespace AloongJerr\Accounting\Facades;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Ledger\AccountLedger;
use AloongJerr\Accounting\Ledger\TAccount;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Reports\BalanceSheet;
use AloongJerr\Accounting\Reports\IncomeStatement;
use AloongJerr\Accounting\Reports\TrialBalance;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Services\AccountingService;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\SnapshotManager;
use AloongJerr\Accounting\Transactions\AdjustmentTransaction;
use AloongJerr\Accounting\Transactions\ManualJournal;
use AloongJerr\Accounting\Transactions\PaidTransaction;
use AloongJerr\Accounting\Transactions\PurchasedTransaction;
use AloongJerr\Accounting\Transactions\ReceivedTransaction;
use AloongJerr\Accounting\Transactions\SoldTransaction;
use BackedEnum;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ManualJournal journal(string $description = '')
 * @method static ReceivedTransaction received(int $amount, string $description = '')
 * @method static PaidTransaction paid(int $amount, string $description = '')
 * @method static SoldTransaction sold(int $amount, string $description = '')
 * @method static PurchasedTransaction purchased(int $amount, string $description = '')
 * @method static AdjustmentTransaction adjustment(int $amount, string $description = '')
 * @method static AccountResolver resolver()
 * @method static AccountLedger ledger(AccountSystemKey|BackedEnum|Account $account)
 * @method static TAccount tAccount(AccountSystemKey|BackedEnum|Account $account)
 * @method static TrialBalance trialBalance()
 * @method static IncomeStatement incomeStatement()
 * @method static BalanceSheet balanceSheet()
 * @method static SnapshotManager snapshot()
 * @method static BalanceService balanceService()
 *
 * @see \AloongJerr\Accounting\Services\AccountingService
 */
class Accounting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountingService::class;
    }
}
