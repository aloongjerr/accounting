<?php

namespace AloongJerr\Accounting\Filament\Pages;

use AloongJerr\Accounting\AccountingPlugin;
use AloongJerr\Accounting\Reports\BalanceSheet as BalanceSheetReport;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class BalanceSheet extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 12;

    protected string $view = 'accounting::filament.pages.balance-sheet';

    public ?string $date = null;

    public array $reportData = [];

    public function getTitle(): string|Htmlable
    {
        return __('accounting::accounting.reports.balance_sheet.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::accounting.reports.balance_sheet.navigation_label');
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
        $this->form->fill([
            'date' => now()->format('Y-m-d'),
        ]);
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label(__('accounting::accounting.reports.fields.as_of_date'))
                    ->required()
                    ->native(false),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $date = $this->form->getState()['date'] ?? now()->format('Y-m-d');
        $report = new BalanceSheetReport($date);

        $this->reportData = [
            'asset_rows' => $report->getAssetRows(),
            'liability_rows' => $report->getLiabilityRows(),
            'equity_rows' => $report->getEquityRows(),
            'total_assets' => $report->getTotalAssets(),
            'total_liabilities' => $report->getTotalLiabilities(),
            'total_equity' => $report->getTotalEquity(),
            'date' => $date,
        ];
    }
}
