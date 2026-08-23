<?php

namespace AloongJerr\Accounting\Facades;

use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Services\AccountingService;
use AloongJerr\Accounting\Transactions\AdjustmentTransaction;
use AloongJerr\Accounting\Transactions\ManualJournal;
use AloongJerr\Accounting\Transactions\PaidTransaction;
use AloongJerr\Accounting\Transactions\PurchasedTransaction;
use AloongJerr\Accounting\Transactions\ReceivedTransaction;
use AloongJerr\Accounting\Transactions\SoldTransaction;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ManualJournal journal(string $description = '')
 * @method static ReceivedTransaction received(int $amount, string $description = '')
 * @method static PaidTransaction paid(int $amount, string $description = '')
 * @method static SoldTransaction sold(int $amount, string $description = '')
 * @method static PurchasedTransaction purchased(int $amount, string $description = '')
 * @method static AdjustmentTransaction adjustment(int $amount, string $description = '')
 * @method static AccountResolver resolver()
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
