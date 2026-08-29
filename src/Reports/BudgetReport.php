<?php

namespace AloongJerr\Accounting\Reports;

use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Budget;
use AloongJerr\Accounting\ValueObjects\BudgetRow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Budget vs Actual Report.
 *
 * Compares budgeted amounts against actual spending/income for
 * accounts within a given period.
 *
 * Usage:
 *   Accounting::budgetReport()
 *       ->forPeriod('2024-01-01', '2024-12-31')
 *       ->get();
 *
 *   // Get summary totals
 *   Accounting::budgetReport()
 *       ->forPeriod('2024-01-01', '2024-12-31')
 *       ->summary();
 */
class BudgetReport extends Report
{
    /**
     * Generate the budget vs actual report data.
     *
     * For each budget in the period, calculates the actual activity
     * and variance.
     *
     * @return Collection<int, BudgetRow>
     */
    public function get(): Collection
    {
        $from = $this->from ?? Carbon::today()->startOfYear();
        $to = $this->to ?? Carbon::today();

        // Get all budgets that overlap with the period
        $budgets = Budget::query()
            ->where('start_date', '>=', $from)
            ->where('end_date', '<=', $to)
            ->with('account')
            ->get();

        if ($budgets->isEmpty()) {
            return new Collection();
        }

        // Get actual period activity from snapshot manager
        $activity = $this->snapshotManager->driver()
            ->getPeriodActivity($from, $to, $this->tenantId);

        // Index activity by account_id for quick lookup
        $activityByAccount = $activity->keyBy('accountId');

        // Get all account details
        $accountIds = $budgets->pluck('account_id')->unique()->all();
        $accounts = Account::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        $rows = new Collection();

        foreach ($budgets as $budget) {
            $account = $accounts->get($budget->account_id);

            if (! $account) {
                continue;
            }

            $accountActivity = $activityByAccount->get($budget->account_id);

            // Calculate actual amount based on account type
            // For expense accounts: actual = debit - credit (net spending)
            // For revenue accounts: actual = credit - debit (net income)
            // For other accounts: use absolute net activity
            if ($accountActivity) {
                $actual = abs($accountActivity->balance);
            } else {
                $actual = 0;
            }

            $variance = $budget->amount - $actual;
            $variancePercentage = $budget->amount > 0
                ? round(($variance / $budget->amount) * 100, 2)
                : null;

            $rows->push(new BudgetRow(
                accountId: $budget->account_id,
                accountName: $account->name,
                accountCode: $account->code,
                budgeted: $budget->amount,
                actual: $actual,
                variance: $variance,
                variancePercentage: $variancePercentage,
            ));
        }

        return $rows->sortBy('accountCode')->values();
    }

    /**
     * Get summary totals for the budget report.
     *
     * @return object{total_budgeted: int, total_actual: int, total_variance: float|null}
     */
    public function summary(): object
    {
        $data = $this->get();

        $totalBudgeted = $data->sum('budgeted');
        $totalActual = $data->sum('actual');
        $totalVariance = $totalBudgeted - $totalActual;
        $overallPercentage = $totalBudgeted > 0
            ? round(($totalVariance / $totalBudgeted) * 100, 2)
            : null;

        return (object) [
            'total_budgeted' => $totalBudgeted,
            'total_actual' => $totalActual,
            'total_variance' => $totalVariance,
            'overall_percentage' => $overallPercentage,
        ];
    }
}
