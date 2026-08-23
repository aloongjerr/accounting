<?php

namespace AloongJerr\Accounting\Enums;

use Filament\Support\Contracts\HasLabel;

enum JournalStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Void = 'void';
    case Reversed = 'reversed';

    public function getLabel(): string
    {
        return __('accounting::accounting.journal_status.' . $this->value);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Void, self::Reversed]);
    }
}
