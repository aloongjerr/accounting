<?php

namespace AloongJerr\Accounting\Filament\Resources\JournalResource\Pages;

use AloongJerr\Accounting\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;

    public function getTitle(): string
    {
        return __('accounting::accounting.resources.journal.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
