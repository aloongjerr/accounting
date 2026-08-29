<?php

namespace AloongJerr\Accounting\Filament\Resources\AccountResource\Pages;

use AloongJerr\Accounting\Filament\Resources\AccountResource;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    public function getTitle(): string
    {
        return __('accounting::accounting.resources.account.pages.list.title');
    }
}
