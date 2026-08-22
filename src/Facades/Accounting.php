<?php

namespace AloongJerr\Accounting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AloongJerr\Accounting\Accounting
 */
class Accounting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AloongJerr\Accounting\Accounting::class;
    }
}
