<?php

namespace AloongJerr\Accounting\Filament\Resources\JournalResource\Pages;

use AloongJerr\Accounting\Filament\Resources\JournalResource;
use Filament\Resources\Pages\ListRecords;

class ListJournals extends ListRecords
{
    protected static string $resource = JournalResource::class;

    public function getTitle(): string
    {
        return __('accounting::accounting.resources.journal.pages.list.title');
    }
}
