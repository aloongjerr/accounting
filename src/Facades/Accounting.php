<?php

namespace AloongJerr\Accounting\Facades;

use AloongJerr\Accounting\Services\AccountingService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \AloongJerr\Accounting\Transactions\ManualJournal journal(string $description = '')
 * @method static mixed received(int $amount, string $description = '')
 * @method static mixed paid(int $amount, string $description = '')
 * @method static mixed sold(int $amount, string $description = '')
 * @method static mixed purchased(int $amount, string $description = '')
 * @method static mixed adjustment(int $amount, string $description = '')
 * @method static \AloongJerr\Accounting\Resolvers\AccountResolver resolver()
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
