<?php

namespace AloongJerr\Accounting\Filament\Pages;

use AloongJerr\Accounting\AccountingPlugin;
use AloongJerr\Accounting\Reports\TrialBalance as TrialBalanceReport;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class TrialBalance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?int $navigationSort = 10;

    protected string $view = 'accounting::filament.pages.trial-balance';

    public ?string $date = null;

    public array $reportData = [];

    public function getTitle(): string|Htmlable
    {
        return __('accounting::accounting.reports.trial_balance.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::accounting.reports.trial_balance.navigation_label');
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
        $report = new TrialBalanceReport($date);

        $this->reportData = [
            'rows' => $report->getRows(),
            'total_debit' => $report->getTotalDebit(),
            'total_credit' => $report->getTotalCredit(),
            'is_balanced' => $report->isBalanced(),
            'date' => $date,
        ];
    }
}
