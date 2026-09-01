<?php

namespace AloongJerr\Accounting\Filament\Resources;

use AloongJerr\Accounting\AccountingPlugin;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Filament\Resources\AccountResource\Pages;
use AloongJerr\Accounting\Models\Account;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 1;

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
        return __('accounting::accounting.resources.account.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::accounting.resources.account.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::accounting.resources.account.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->components([
                        Forms\Components\TextInput::make('code')
                            ->label(__('accounting::accounting.resources.account.fields.code'))
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit'),

                        Forms\Components\TextInput::make('name')
                            ->label(__('accounting::accounting.resources.account.fields.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label(__('accounting::accounting.resources.account.fields.type'))
                            ->options(AccountType::class)
                            ->required()
                            ->disabledOn('edit')
                            ->native(false),

                        Forms\Components\Select::make('system_key')
                            ->label(__('accounting::accounting.resources.account.fields.system_key'))
                            ->options(self::getSystemKeyOptions())
                            ->searchable()
                            ->native(false)
                            ->disabledOn('edit'),

                        Forms\Components\Textarea::make('description')
                            ->label(__('accounting::accounting.resources.account.fields.description'))
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('accounting::accounting.resources.account.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('accounting::accounting.resources.account.fields.code'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('accounting::accounting.resources.account.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('accounting::accounting.resources.account.fields.type'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('system_key')
                    ->label(__('accounting::accounting.resources.account.fields.system_key'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('accounting::accounting.resources.account.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label(__('accounting::accounting.resources.account.fields.balance'))
                    ->state(fn (Account $record): string => number_format($record->getBalance() / 100, 2))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('accounting::accounting.resources.account.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('accounting::accounting.resources.account.fields.type'))
                    ->options(AccountType::class),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('accounting::accounting.resources.account.fields.is_active')),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'view' => Pages\ViewAccount::route('/{record}'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    /**
     * Get system key options for the select field.
     * Combines default AccountSystemKey enum with any custom registered keys.
     * Options are grouped by their root parent (Assets, Liabilities, etc.).
     */
    protected static function getSystemKeyOptions(): array
    {
        $options = [];

        foreach (AccountSystemKey::cases() as $case) {
            $group = self::getRootGroup($case);
            $options[$group->getLabel()][$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Traverse up the parent chain to find the root group.
     */
    protected static function getRootGroup(AccountSystemKey $case): AccountSystemKey
    {
        while ($case->parentKey() !== null) {
            $case = $case->parentKey();
        }

        return $case;
    }
}
