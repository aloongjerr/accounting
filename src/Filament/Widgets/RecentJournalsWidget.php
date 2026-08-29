<?php

namespace AloongJerr\Accounting\Filament\Widgets;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Journal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentJournalsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = null;

    public function getTableHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('accounting::accounting.widgets.recent_journals.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Journal::query()
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('accounting::accounting.widgets.recent_journals.date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('accounting::accounting.widgets.recent_journals.description'))
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('accounting::accounting.widgets.recent_journals.status'))
                    ->badge(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('accounting::accounting.widgets.recent_journals.total'))
                    ->state(fn (Journal $record): string => number_format($record->totalDebit() / 100, 2))
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
