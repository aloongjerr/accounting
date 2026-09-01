<?php

namespace AloongJerr\Accounting\Filament\Pages;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\AccountingPlugin;
use AloongJerr\Accounting\Reports\IncomeStatement as IncomeStatementReport;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class IncomeStatement extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 11;

    protected string $view = 'accounting::filament.pages.income-statement';

    public ?array $data = [];

    public array $reportData = [];

    public function getTitle(): string|Htmlable
    {
        return __('accounting::accounting.reports.income_statement.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::accounting.reports.income_statement.navigation_label');
    }

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

    public function mount(): void
    {
        $fiscalYear = $this->getDefaultDateRange();

        $this->form->fill([
            'start_date' => $fiscalYear['start'],
            'end_date' => $fiscalYear['end'],
        ]);
        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('accounting::accounting.reports.fields.start_date'))
                    ->required()
                    ->native(false),

                Forms\Components\DatePicker::make('end_date')
                    ->label(__('accounting::accounting.reports.fields.end_date'))
                    ->required()
                    ->native(false),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $state = $this->form->getState();
        $startDate = $state['start_date'] ?? now()->startOfYear()->format('Y-m-d');
        $endDate = $state['end_date'] ?? now()->endOfYear()->format('Y-m-d');

        $report = (new IncomeStatementReport())->forPeriod($startDate, $endDate);

        $this->reportData = [
            'income_rows' => $report->getIncomeRows(),
            'expense_rows' => $report->getExpenseRows(),
            'total_income' => $report->getTotalRevenue(),
            'total_expenses' => $report->getTotalExpenses(),
            'net_profit' => $report->getNetProfit(),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function getDefaultDateRange(): array
    {
        $config = Accounting::config('fiscal_year');
        $year = now()->year;

        return [
            'start' => "{$year}-01-01",
            'end' => now()->format('Y-m-d'),
        ];
    }
}
