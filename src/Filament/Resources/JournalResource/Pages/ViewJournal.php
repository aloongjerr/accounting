<?php

namespace AloongJerr\Accounting\Filament\Resources\JournalResource\Pages;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Filament\Resources\JournalResource;
use AloongJerr\Accounting\Models\Journal;
use Filament\Actions;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ViewJournal extends ViewRecord
{
    protected static string $resource = JournalResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Journal $record */
        $record = $this->getRecord();

        return "#{$record->id} - {$record->description}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\Section::make(__('accounting::accounting.resources.journal.sections.details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ->label(__('accounting::accounting.resources.journal.fields.date'))
                            ->date(),

                        Infolists\Components\TextEntry::make('description')
                            ->label(__('accounting::accounting.resources.journal.fields.description')),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('accounting::accounting.resources.journal.fields.status'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('reference_type')
                            ->label(__('accounting::accounting.resources.journal.fields.reference_type'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('comments')
                            ->label(__('accounting::accounting.resources.journal.fields.comments'))
                            ->listWithLineBreaks()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('void_remarks')
                            ->label(__('accounting::accounting.resources.journal.fields.void_remarks'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('accounting::accounting.resources.journal.sections.entries'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('entries')
                            ->schema([
                                Infolists\Components\TextEntry::make('account.name')
                                    ->label(__('accounting::accounting.resources.journal.fields.account')),

                                Infolists\Components\TextEntry::make('debit')
                                    ->label(__('accounting::accounting.resources.journal.fields.debit'))
                                    ->state(fn ($record): string => $record->debit > 0 ? number_format($record->debit / 100, 2) : '-')
                                    ->alignEnd(),

                                Infolists\Components\TextEntry::make('credit')
                                    ->label(__('accounting::accounting.resources.journal.fields.credit'))
                                    ->state(fn ($record): string => $record->credit > 0 ? number_format($record->credit / 100, 2) : '-')
                                    ->alignEnd(),

                                Infolists\Components\TextEntry::make('description')
                                    ->label(__('accounting::accounting.resources.journal.fields.entry_description'))
                                    ->placeholder('-'),
                            ])
                            ->columns(4),
                    ]),

                Infolists\Components\Section::make(__('accounting::accounting.resources.journal.sections.summary'))
                    ->schema([
                        Infolists\Components\TextEntry::make('total_debit')
                            ->label(__('accounting::accounting.resources.journal.fields.total_debit'))
                            ->state(fn (Journal $record): string => number_format($record->totalDebit() / 100, 2)),

                        Infolists\Components\TextEntry::make('total_credit')
                            ->label(__('accounting::accounting.resources.journal.fields.total_credit'))
                            ->state(fn (Journal $record): string => number_format($record->totalCredit() / 100, 2)),

                        Infolists\Components\TextEntry::make('balanced')
                            ->label(__('accounting::accounting.resources.journal.fields.balanced'))
                            ->state(fn (Journal $record): string => $record->isBalanced() ? __('accounting::accounting.status.yes') : __('accounting::accounting.status.no'))
                            ->color(fn (Journal $record): string => $record->isBalanced() ? 'success' : 'danger'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (Journal $record): bool => $record->status === JournalStatus::Draft),

            Actions\Action::make('post')
                ->label(__('accounting::accounting.resources.journal.actions.post'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Journal $record): bool => $record->status === JournalStatus::Draft && $record->isBalanced())
                ->action(fn (Journal $record) => $record->post()),

            Actions\Action::make('void')
                ->label(__('accounting::accounting.resources.journal.actions.void'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Journal $record): bool => ! $record->status->isFinal())
                ->form([
                    \Filament\Forms\Components\Textarea::make('void_remarks')
                        ->label(__('accounting::accounting.resources.journal.fields.void_remarks'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (Journal $record, array $data) {
                    $record->void($data['void_remarks']);
                }),

            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
