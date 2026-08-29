<?php

namespace AloongJerr\Accounting\Filament\Resources\AccountResource\Pages;

use AloongJerr\Accounting\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    public function getTitle(): string
    {
        return __('accounting::accounting.resources.account.pages.edit.title');
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
