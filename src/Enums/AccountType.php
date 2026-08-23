<?php

namespace AloongJerr\Accounting\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case Group = 'group';
    case Category = 'category';
    case Account = 'account';

    public function getLabel(): string
    {
        return __('accounting::accounting.account_type.' . $this->value);
    }
}
