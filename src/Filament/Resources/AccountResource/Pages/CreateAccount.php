<?php

namespace AloongJerr\Accounting\Filament\Resources\AccountResource\Pages;

use AloongJerr\Accounting\Filament\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    public function getTitle(): string
    {
        return __('accounting::accounting.resources.account.pages.create.title');
    }
}
