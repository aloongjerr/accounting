<?php

namespace AloongJerr\Accounting\Filament\Widgets;

use AloongJerr\Accounting\Reports\IncomeStatement as IncomeStatementReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialSummaryWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = null;

    protected function getStats(): array
    {
        $report = new IncomeStatementReport(
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $summary = $report->summary();

        $totalIncome = $summary->revenue / 100;
        $totalExpenses = $summary->expenses / 100;
        $netProfit = $summary->net_profit / 100;

        return [
            Stat::make(
                __('accounting::accounting.widgets.financial_summary.income'),
                number_format($totalIncome, 2)
            )
                ->description(__('accounting::accounting.widgets.financial_summary.income_description', ['year' => now()->year]))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make(
                __('accounting::accounting.widgets.financial_summary.expenses'),
                number_format($totalExpenses, 2)
            )
                ->description(__('accounting::accounting.widgets.financial_summary.expenses_description', ['year' => now()->year]))
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger'),

            Stat::make(
                __('accounting::accounting.widgets.financial_summary.net_profit'),
                number_format($netProfit, 2)
            )
                ->description(__('accounting::accounting.widgets.financial_summary.net_profit_description'))
                ->icon('heroicon-o-presentation-chart-line')
                ->color($netProfit >= 0 ? 'success' : 'danger'),
        ];
    }
}
