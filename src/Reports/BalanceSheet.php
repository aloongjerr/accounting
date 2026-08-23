<?php

namespace AloongJerr\Accounting\Reports;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Balance Sheet report.
 *
 * Shows assets, liabilities, and equity as of a specific date.
 * Must satisfy: Assets = Liabilities + Equity
 *
 * Usage:
 *   Accounting::balanceSheet()
 *       ->asOf('2024-12-31')
 *       ->get();
 *
 *   // Or use fiscal year
 *   Accounting::balanceSheet()
 *       ->forFiscalYear(2024)
 *       ->get();
 */
class BalanceSheet extends Report
{
    /**
     * Generate the balance sheet data.
     *
     * @return Collection<int, object> Account balances grouped by type
     */
    public function get(): Collection
    {
        $asOf = $this->asOf ?? Carbon::today();

        // Get cumulative balances from snapshot driver
        $balances = $this->snapshotManager->driver()
            ->getCumulativeBalances($asOf, $this->tenantId);

        // Enrich with account details
        $enriched = $this->enrichWithAccountDetails($balances);

        // Filter to only balance sheet accounts (assets, liabilities, equity)
        $bsAccounts = $enriched->filter(function ($item) {
            return $this->isBalanceSheetAccount($item);
        });

        // Roll up to parent accounts
        return $this->rollupBalances($bsAccounts);
    }

    /**
     * Get summary for the balance sheet.
     *
     * @return object{assets: int, liabilities: int, equity: int, retained_earnings: int, is_balanced: bool}
     */
    public function summary(): object
    {
        $data = $this->get();

        // Only count leaf accounts for totals
        $leafData = $data->filter(fn ($item) => $item->accountType->value === 'account');

        $assets = 0;
        $liabilities = 0;
        $equity = 0;

        foreach ($leafData as $item) {
            if ($this->isAssetAccount($item)) {
                // Assets have debit normal balance
                $assets += $item->balance;
            } elseif ($this->isLiabilityAccount($item)) {
                // Liabilities have credit normal balance
                $liabilities += -$item->balance;
            } elseif ($this->isEquityAccount($item)) {
                // Equity has credit normal balance
                $equity += -$item->balance;
            }
        }

        // Calculate retained earnings from net profit
        $retainedEarnings = $this->calculateRetainedEarnings();
        $totalEquity = $equity + $retainedEarnings;

        return (object) [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $totalEquity,
            'retained_earnings' => $retainedEarnings,
            'is_balanced' => $assets === ($liabilities + $totalEquity),
            'difference' => $assets - ($liabilities + $totalEquity),
        ];
    }

    /**
     * Calculate retained earnings from net profit (revenue - expenses).
     */
    protected function calculateRetainedEarnings(): int
    {
        $incomeStatement = new IncomeStatement();

        if ($this->from && $this->to) {
            $incomeStatement->forPeriod($this->from, $this->to);
        } elseif ($this->asOf) {
            // Use fiscal year start to asOf date
            $fyStart = $this->resolveFiscalYearStart($this->asOf);
            $incomeStatement->forPeriod($fyStart, $this->asOf);
        }

        if ($this->tenantId !== null) {
            $incomeStatement->forTenant($this->tenantId);
        }

        return $incomeStatement->summary()->net_profit;
    }

    /**
     * Resolve the fiscal year start date for a given date.
     */
    protected function resolveFiscalYearStart(Carbon $date): Carbon
    {
        $startMonth = (int) Accounting::config('fiscal_year.start_month', 1);
        $startDay = (int) Accounting::config('fiscal_year.start_day', 1);

        $fyStart = Carbon::createFromDate($date->year, $startMonth, $startDay);

        // If the fiscal year hasn't started yet this calendar year, use previous year
        if ($fyStart->isAfter($date)) {
            $fyStart->subYear();
        }

        return $fyStart;
    }

    /**
     * Check if an account is a balance sheet account.
     */
    protected function isBalanceSheetAccount(object $item): bool
    {
        return $this->isAssetAccount($item)
            || $this->isLiabilityAccount($item)
            || $this->isEquityAccount($item);
    }

    /**
     * Check if an account is an asset account.
     */
    protected function isAssetAccount(object $item): bool
    {
        $systemKey = $item->systemKey;

        if (! $systemKey) {
            return false;
        }

        return in_array($systemKey, [
            AccountSystemKey::Assets,
            AccountSystemKey::CurrentAssets,
            AccountSystemKey::FixedAssets,
            AccountSystemKey::CashOnHand,
            AccountSystemKey::CashInBank,
            AccountSystemKey::AccountsReceivable,
            AccountSystemKey::Inventory,
            AccountSystemKey::PrepaidExpenses,
            AccountSystemKey::TaxReceivable,
            AccountSystemKey::Land,
            AccountSystemKey::Building,
            AccountSystemKey::Equipment,
            AccountSystemKey::Vehicle,
            AccountSystemKey::FurnitureAndFixtures,
            AccountSystemKey::AccumulatedDepreciation,
        ]);
    }

    /**
     * Check if an account is a liability account.
     */
    protected function isLiabilityAccount(object $item): bool
    {
        $systemKey = $item->systemKey;

        if (! $systemKey) {
            return false;
        }

        return in_array($systemKey, [
            AccountSystemKey::Liabilities,
            AccountSystemKey::CurrentLiabilities,
            AccountSystemKey::LongTermLiabilities,
            AccountSystemKey::AccountsPayable,
            AccountSystemKey::AccruedExpenses,
            AccountSystemKey::ShortTermLoans,
            AccountSystemKey::TaxPayable,
            AccountSystemKey::WagesPayable,
            AccountSystemKey::LongTermLoans,
            AccountSystemKey::MortgagePayable,
        ]);
    }

    /**
     * Check if an account is an equity account.
     */
    protected function isEquityAccount(object $item): bool
    {
        $systemKey = $item->systemKey;

        if (! $systemKey) {
            return false;
        }

        return in_array($systemKey, [
            AccountSystemKey::Equity,
            AccountSystemKey::OwnerEquity,
            AccountSystemKey::OwnerCapital,
            AccountSystemKey::OwnerDrawings,
            AccountSystemKey::RetainedEarnings,
            AccountSystemKey::ShareCapital,
        ]);
    }
}
