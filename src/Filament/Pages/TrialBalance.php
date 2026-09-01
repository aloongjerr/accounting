<?php

namespace AloongJerr\Accounting\Filament\Pages;

use AloongJerr\Accounting\AccountingPlugin;
use AloongJerr\Accounting\Reports\TrialBalance as TrialBalanceReport;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class TrialBalance extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?int $navigationSort = 10;

    protected string $view = 'accounting::filament.pages.trial-balance';

    public ?string $date = null;

    public ?array $data = [];

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
        $report = (new TrialBalanceReport())->asOf($date);
        $summary = $report->summary();

        $this->reportData = [
            'rows' => $report->get(),
            'total_debit' => $summary->total_debit,
            'total_credit' => $summary->total_credit,
            'is_balanced' => $summary->is_balanced,
            'date' => $date,
        ];
    }
}
