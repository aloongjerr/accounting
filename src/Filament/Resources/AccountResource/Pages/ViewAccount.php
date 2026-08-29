<?php

namespace AloongJerr\Accounting\Filament\Resources\AccountResource\Pages;

use AloongJerr\Accounting\Filament\Resources\AccountResource;
use AloongJerr\Accounting\Ledger\AccountLedger;
use AloongJerr\Accounting\Ledger\TAccount;
use AloongJerr\Accounting\Models\Account;
use Filament\Actions;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Account $record */
        $record = $this->getRecord();

        return "{$record->code} - {$record->name}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\Section::make(__('accounting::accounting.resources.account.sections.details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label(__('accounting::accounting.resources.account.fields.code')),

                        Infolists\Components\TextEntry::make('name')
                            ->label(__('accounting::accounting.resources.account.fields.name')),

                        Infolists\Components\TextEntry::make('type')
                            ->label(__('accounting::accounting.resources.account.fields.type'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('system_key')
                            ->label(__('accounting::accounting.resources.account.fields.system_key')),

                        Infolists\Components\TextEntry::make('parent.name')
                            ->label(__('accounting::accounting.resources.account.fields.parent'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('description')
                            ->label(__('accounting::accounting.resources.account.fields.description'))
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('accounting::accounting.resources.account.fields.is_active')),

                        Infolists\Components\TextEntry::make('balance')
                            ->label(__('accounting::accounting.resources.account.fields.balance'))
                            ->state(fn (Account $record): string => number_format($record->getBalance() / 100, 2)),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Get the ledger table for this account.
     */
    public function ledgerTable(Table $table): Table
    {
        /** @var Account $account */
        $account = $this->getRecord();

        return $table
            ->query($account->journalEntries()->with('journal')->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('journal.date')
                    ->label(__('accounting::accounting.resources.account.fields.date'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('journal.description')
                    ->label(__('accounting::accounting.resources.account.fields.description')),

                Tables\Columns\TextColumn::make('debit')
                    ->label(__('accounting::accounting.resources.journal.fields.debit'))
                    ->state(fn ($record): string => $record->debit > 0 ? number_format($record->debit / 100, 2) : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('credit')
                    ->label(__('accounting::accounting.resources.journal.fields.credit'))
                    ->state(fn ($record): string => $record->credit > 0 ? number_format($record->credit / 100, 2) : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('running_balance')
                    ->label(__('accounting::accounting.resources.account.fields.running_balance'))
                    ->state(function ($record) use ($account): string {
                        $ledger = new AccountLedger($account);
                        $balance = $ledger->getRunningBalance($record);

                        return number_format($balance / 100, 2);
                    })
                    ->alignEnd(),
            ])
            ->defaultSort('journal.date', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Get the T-Account table for this account.
     */
    public function tAccountTable(Table $table): Table
    {
        /** @var Account $account */
        $account = $this->getRecord();
        $tAccount = new TAccount($account);

        return $table
            ->query($account->journalEntries()->with('journal')->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('journal.date')
                    ->label(__('accounting::accounting.resources.account.fields.date'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('journal.description')
                    ->label(__('accounting::accounting.resources.account.fields.description')),

                Tables\Columns\TextColumn::make('debit')
                    ->label(__('accounting::accounting.resources.journal.fields.debit'))
                    ->state(fn ($record): string => $record->debit > 0 ? number_format($record->debit / 100, 2) : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('credit')
                    ->label(__('accounting::accounting.resources.journal.fields.credit'))
                    ->state(fn ($record): string => $record->credit > 0 ? number_format($record->credit / 100, 2) : '-')
                    ->alignEnd(),
            ])
            ->defaultSort('journal.date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->footerContent(view('accounting::t-account-footer', ['tAccount' => $tAccount]));
    }
}
