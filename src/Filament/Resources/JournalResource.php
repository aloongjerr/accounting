<?php

namespace AloongJerr\Accounting\Filament\Resources;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\AccountingPlugin;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Filament\Resources\JournalResource\Pages;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Journal;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $plugin = filament()->getPlugin('accounting');

        if ($plugin instanceof AccountingPlugin) {
            $group = $plugin->getResourceNavigationGroup();

            if ($group instanceof \Closure) {
                return ($group)();
            }

            return $group ?? __('accounting::accounting.navigation.finance');
        }

        return __('accounting::accounting.navigation.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::accounting.resources.journal.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::accounting.resources.journal.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::accounting.resources.journal.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make()
                    ->components([
                        Forms\Components\DatePicker::make('date')
                            ->label(__('accounting::accounting.resources.journal.fields.date'))
                            ->required()
                            ->default(now())
                            ->disabledOn('edit'),

                        Forms\Components\TextInput::make('description')
                            ->label(__('accounting::accounting.resources.journal.fields.description'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('comments')
                            ->label(__('accounting::accounting.resources.journal.fields.comments'))
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('accounting::accounting.resources.journal.sections.entries'))
                    ->components([
                        Forms\Components\Repeater::make('entries')
                            ->relationship()
                            ->components([
                                Forms\Components\Select::make('account_id')
                                    ->label(__('accounting::accounting.resources.journal.fields.account'))
                                    ->options(Account::leaf()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabledOn('edit')
                                    ->native(false),

                                Forms\Components\TextInput::make('debit')
                                    ->label(__('accounting::accounting.resources.journal.fields.debit'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabledOn('edit')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('credit')
                                    ->label(__('accounting::accounting.resources.journal.fields.credit'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabledOn('edit')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('description')
                                    ->label(__('accounting::accounting.resources.journal.fields.entry_description'))
                                    ->maxLength(255)
                                    ->disabledOn('edit'),
                            ])
                            ->columns(4)
                            ->defaultItems(2)
                            ->minItems(2)
                            ->disabledOn('edit')
                            ->addActionLabel(__('accounting::accounting.resources.journal.actions.add_entry')),
                    ])
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('accounting::accounting.resources.journal.fields.date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('accounting::accounting.resources.journal.fields.description'))
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('accounting::accounting.resources.journal.fields.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_debit')
                    ->label(__('accounting::accounting.resources.journal.fields.total'))
                    ->state(fn (Journal $record): string => number_format($record->totalDebit() / 100, 2))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('reference_type')
                    ->label(__('accounting::accounting.resources.journal.fields.reference'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('void_remarks')
                    ->label(__('accounting::accounting.resources.journal.fields.void_remarks'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('accounting::accounting.resources.journal.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('accounting::accounting.resources.journal.fields.status'))
                    ->options(JournalStatus::class),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label(__('accounting::accounting.resources.journal.fields.date_from')),
                        Forms\Components\DatePicker::make('date_to')
                            ->label(__('accounting::accounting.resources.journal.fields.date_to')),
                    ])
                    ->query(function ($query, array $data) {
                        $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['date_to'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Journal $record): bool => $record->status === JournalStatus::Draft),

                // Post action - only for draft journals
                Tables\Actions\Action::make('post')
                    ->label(__('accounting::accounting.resources.journal.actions.post'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Journal $record): bool => $record->status === JournalStatus::Draft && $record->isBalanced())
                    ->action(fn (Journal $record) => $record->post()),

                // Void action - requires reason
                Tables\Actions\Action::make('void')
                    ->label(__('accounting::accounting.resources.journal.actions.void'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Journal $record): bool => ! $record->status->isFinal())
                    ->form([
                        Forms\Components\Textarea::make('void_remarks')
                            ->label(__('accounting::accounting.resources.journal.fields.void_remarks'))
                            ->required()
                            ->rows(3)
                            ->placeholder(__('accounting::accounting.resources.journal.placeholders.void_remarks')),
                    ])
                    ->action(function (Journal $record, array $data) {
                        $record->void($data['void_remarks']);
                    }),

                // Adjustment action - always available
                Tables\Actions\Action::make('adjust')
                    ->label(__('accounting::accounting.resources.journal.actions.adjust'))
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\Select::make('account_id')
                            ->label(__('accounting::accounting.resources.journal.fields.account'))
                            ->options(Account::leaf()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('accounting::accounting.resources.journal.fields.amount'))
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('description')
                            ->label(__('accounting::accounting.resources.journal.fields.description'))
                            ->required(),
                    ])
                    ->action(function (Journal $record, array $data) {
                        Accounting::adjustment($data['amount'], $data['description'])
                            ->forAccount($data['account_id'])
                            ->reference($record)
                            ->commit();
                    }),

                // Create Transaction action - only when enabled
                Tables\Actions\Action::make('create_transaction')
                    ->label(__('accounting::accounting.resources.journal.actions.create_transaction'))
                    ->icon('heroicon-o-arrow-trending-up')
                    ->visible(fn (): bool => static::isCreateTransactionEnabled())
                    ->form([
                        Forms\Components\Select::make('transaction_type')
                            ->label(__('accounting::accounting.resources.journal.fields.transaction_type'))
                            ->options([
                                'received' => __('accounting::accounting.transactions.received'),
                                'paid' => __('accounting::accounting.transactions.paid'),
                                'sold' => __('accounting::accounting.transactions.sold'),
                                'purchased' => __('accounting::accounting.transactions.purchased'),
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('accounting::accounting.resources.journal.fields.amount'))
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('description')
                            ->label(__('accounting::accounting.resources.journal.fields.description'))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $transaction = match ($data['transaction_type']) {
                            'received' => Accounting::received($data['amount'], $data['description']),
                            'paid' => Accounting::paid($data['amount'], $data['description']),
                            'sold' => Accounting::sold($data['amount'], $data['description']),
                            'purchased' => Accounting::purchased($data['amount'], $data['description']),
                        };

                        $transaction->commit();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'view' => Pages\ViewJournal::route('/{record}'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }

    /**
     * Check if transaction creation is enabled via plugin.
     */
    protected static function isCreateTransactionEnabled(): bool
    {
        $plugin = filament()->getPlugin('accounting');

        if ($plugin instanceof AccountingPlugin) {
            return $plugin->isCreateTransactionEnabled();
        }

        return false;
    }
}
