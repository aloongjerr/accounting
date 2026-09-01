<?php

namespace AloongJerr\Accounting\Reports;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Income Statement (Profit & Loss) report.
 *
 * Shows revenue and expenses for a period, calculating net profit or loss.
 *
 * Usage:
 *   Accounting::incomeStatement()
 *       ->forPeriod('2024-01-01', '2024-12-31')
 *       ->get();
 *
 *   // Or use fiscal year
 *   Accounting::incomeStatement()
 *       ->forFiscalYear(2024)
 *       ->get();
 */
class IncomeStatement extends Report
{
    /**
     * Generate the income statement data.
     *
     * @return Collection<int, object> Account activity grouped by category
     */
    public function get(): Collection
    {
        $from = $this->from ?? Carbon::today()->startOfYear();
        $to = $this->to ?? Carbon::today();

        // Get period activity from snapshot driver
        $activity = $this->snapshotManager->driver()
            ->getPeriodActivity($from, $to, $this->tenantId);

        // Enrich with account details
        $enriched = $this->enrichWithAccountDetails($activity);

        // Filter to only revenue and expense accounts
        $incomeAccounts = $enriched->filter(function ($item) {
            return $this->isIncomeAccount($item);
        });

        // Roll up to parent accounts
        return $this->rollupBalances($incomeAccounts);
    }

    /**
     * Get summary for the income statement.
     *
     * @return object{revenue: int, expenses: int, net_profit: int}
     */
    public function summary(): object
    {
        $data = $this->get();

        // Only count leaf accounts for totals
        $leafData = $data->filter(fn ($item) => $item->accountType->value === 'account');

        $revenue = 0;
        $expenses = 0;

        foreach ($leafData as $item) {
            if ($this->isRevenueAccount($item)) {
                // Revenue has credit normal balance, so net = credit - debit
                $revenue += ($item->credit - $item->debit);
            } elseif ($this->isExpenseAccount($item)) {
                // Expenses have debit normal balance, so net = debit - credit
                $expenses += ($item->debit - $item->credit);
            }
        }

        return (object) [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => $revenue - $expenses,
        ];
    }

    /**
     * Get income statement rows filtered to revenue accounts only.
     *
     * @return Collection<int, \AloongJerr\Accounting\ValueObjects\ReportRow>
     */
    public function getIncomeRows(): Collection
    {
        return $this->get()->filter(fn ($item) => $this->isRevenueAccount($item))->values();
    }

    /**
     * Get income statement rows filtered to expense accounts only.
     *
     * @return Collection<int, \AloongJerr\Accounting\ValueObjects\ReportRow>
     */
    public function getExpenseRows(): Collection
    {
        return $this->get()->filter(fn ($item) => $this->isExpenseAccount($item))->values();
    }

    /**
     * Get total revenue amount.
     */
    public function getTotalRevenue(): int
    {
        return $this->summary()->revenue;
    }

    /**
     * Get total expenses amount.
     */
    public function getTotalExpenses(): int
    {
        return $this->summary()->expenses;
    }

    /**
     * Get net profit (revenue - expenses).
     */
    public function getNetProfit(): int
    {
        return $this->summary()->net_profit;
    }

    /**
     * Check if an account is an income statement account (revenue or expense).
     */
    protected function isIncomeAccount(object $item): bool
    {
        return $this->isRevenueAccount($item) || $this->isExpenseAccount($item);
    }

    /**
     * Check if an account is a revenue account.
     */
    protected function isRevenueAccount(object $item): bool
    {
        $systemKey = $item->systemKey;

        if (! $systemKey) {
            return false;
        }

        // Check if the account's parent chain leads to Revenue group
        return in_array($systemKey, [
            AccountSystemKey::Revenue,
            AccountSystemKey::OperatingRevenue,
            AccountSystemKey::NonOperatingRevenue,
            AccountSystemKey::ContraRevenue,
            AccountSystemKey::SalesRevenue,
            AccountSystemKey::ServiceRevenue,
            AccountSystemKey::InterestIncome,
            AccountSystemKey::OtherIncome,
            AccountSystemKey::SalesReturnsAndAllowances,
        ]);
    }

    /**
     * Check if an account is an expense account.
     */
    protected function isExpenseAccount(object $item): bool
    {
        $systemKey = $item->systemKey;

        if (! $systemKey) {
            return false;
        }

        return in_array($systemKey, [
            AccountSystemKey::Expenses,
            AccountSystemKey::CostOfGoodsSold,
            AccountSystemKey::OperatingExpenses,
            AccountSystemKey::NonOperatingExpenses,
            AccountSystemKey::CostOfRevenue,
            AccountSystemKey::SalaryExpense,
            AccountSystemKey::RentExpense,
            AccountSystemKey::UtilitiesExpense,
            AccountSystemKey::DepreciationExpense,
            AccountSystemKey::BadDebtExpense,
            AccountSystemKey::InsuranceExpense,
            AccountSystemKey::OfficeSuppliesExpense,
            AccountSystemKey::InterestExpense,
            AccountSystemKey::TaxExpense,
            AccountSystemKey::LossOnDisposal,
        ]);
    }
}
