<?php

namespace AloongJerr\Accounting\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Trial Balance report.
 *
 * Lists all accounts with their cumulative debit and credit balances
 * as of a specific date. Used to verify that total debits equal total credits.
 *
 * Usage:
 *   Accounting::trialBalance()
 *       ->asOf('2024-12-31')
 *       ->get();
 *
 *   // Or use fiscal year defaults
 *   Accounting::trialBalance()
 *       ->forFiscalYear(2024)
 *       ->get();
 */
class TrialBalance extends Report
{
    /**
     * Generate the trial balance data.
     *
     * @return Collection<int, object> Enriched account balances with rollup
     */
    public function get(): Collection
    {
        $asOf = $this->asOf ?? Carbon::today();

        // Get cumulative balances from snapshot driver
        $balances = $this->snapshotManager->driver()
            ->getCumulativeBalances($asOf, $this->tenantId);

        // Enrich with account details
        $enriched = $this->enrichWithAccountDetails($balances);

        // Roll up to parent accounts
        return $this->rollupBalances($enriched);
    }

    /**
     * Get summary totals for the trial balance.
     *
     * @return object{total_debit: int, total_credit: int, is_balanced: bool}
     */
    public function summary(): object
    {
        $data = $this->get();

        // Only count leaf accounts for totals (avoid double-counting from rollup)
        $leafTotals = $data->filter(fn ($item) => $item->accountType->value === 'account');

        $totalDebit = $leafTotals->sum('debit');
        $totalCredit = $leafTotals->sum('credit');

        return (object) [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => $totalDebit === $totalCredit,
            'difference' => $totalDebit - $totalCredit,
        ];
    }
}
