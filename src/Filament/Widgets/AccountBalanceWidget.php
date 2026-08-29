<?php

namespace AloongJerr\Accounting\Filament\Widgets;

use AloongJerr\Accounting\Models\Account;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountBalanceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = null;

    protected function getStats(): array
    {
        $cash = Account::where('system_key', 'cash_in_bank')->first();
        $receivables = Account::where('system_key', 'accounts_receivable')->first();
        $payables = Account::where('system_key', 'accounts_payable')->first();

        return [
            Stat::make(
                __('accounting::accounting.widgets.account_balance.cash'),
                $cash ? number_format($cash->getBalance() / 100, 2) : '0.00'
            )
                ->description(__('accounting::accounting.widgets.account_balance.cash_description'))
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make(
                __('accounting::accounting.widgets.account_balance.receivables'),
                $receivables ? number_format($receivables->getBalance() / 100, 2) : '0.00'
            )
                ->description(__('accounting::accounting.widgets.account_balance.receivables_description'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary'),

            Stat::make(
                __('accounting::accounting.widgets.account_balance.payables'),
                $payables ? number_format(abs($payables->getBalance()) / 100, 2) : '0.00'
            )
                ->description(__('accounting::accounting.widgets.account_balance.payables_description'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger'),
        ];
    }
}
