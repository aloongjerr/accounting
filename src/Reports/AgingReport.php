<?php

namespace AloongJerr\Accounting\Reports;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\JournalEntry;
use AloongJerr\Accounting\ValueObjects\AgingRow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * AR/AP Aging Report.
 *
 * Shows outstanding balances for Accounts Receivable or Accounts Payable,
 * grouped by age buckets (Current, 31-60, 61-90, Over 90 days).
 *
 * Usage:
 *   // Accounts Receivable aging (who owes you)
 *   Accounting::aging()
 *       ->forType(AccountSystemKey::AccountsReceivable)
 *       ->asOf('2024-12-31')
 *       ->get();
 *
 *   // Accounts Payable aging (who you owe)
 *   Accounting::aging()
 *       ->forType(AccountSystemKey::AccountsPayable)
 *       ->asOf(Carbon::today())
 *       ->get();
 *
 *   // Get summary totals per bucket
 *   Accounting::aging()
 *       ->forType(AccountSystemKey::AccountsReceivable)
 *       ->summary();
 */
class AgingReport extends Report
{
    /**
     * Age bucket definitions in days.
     * Each bucket is [min_days, max_days, label].
     */
    protected const BUCKETS = [
        [0, 30, 'current'],
        [31, 60, '31-60'],
        [61, 90, '61-90'],
        [91, PHP_INT_MAX, 'over_90'],
    ];

    protected AccountSystemKey $type = AccountSystemKey::AccountsReceivable;

    /**
     * Set the aging report type (AR or AP).
     */
    public function forType(AccountSystemKey $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Generate the aging report data.
     *
     * Groups all entries by account and calculates the net outstanding
     * balance per account. Uses the oldest entry date for age bucketing.
     *
     * @return Collection<int, AgingRow>
     */
    public function get(): Collection
    {
        $asOf = $this->asOf ?? Carbon::today();

        // Find all leaf accounts under the target system key
        $leafAccounts = $this->getLeafAccountsUnder($this->type);

        if ($leafAccounts->isEmpty()) {
            return new Collection();
        }

        $accountIds = $leafAccounts->pluck('id')->all();
        $accountNames = $leafAccounts->pluck('name', 'id')->all();

        // Get all journal entries for these accounts from posted, non-void journals
        $entries = JournalEntry::query()
            ->whereIn('account_id', $accountIds)
            ->whereHas('journal', function ($query) use ($asOf) {
                $query->where('status', JournalStatus::Posted)
                    ->whereNull('deleted_at')
                    ->where('date', '<=', $asOf);
            })
            ->with('journal')
            ->get();

        // Group entries by account_id to calculate net outstanding per account
        $accountGroups = $entries->groupBy('account_id');

        $rows = new Collection();

        foreach ($accountGroups as $accountId => $accountEntries) {
            $outstanding = $this->calculateOutstanding($accountEntries);

            // Skip if fully settled
            if ($outstanding === 0) {
                continue;
            }

            // Use the oldest journal date for age bucketing
            $oldestDate = $accountEntries->min(fn ($e) => $e->journal->date->timestamp);
            $oldestJournal = $accountEntries->first(fn ($e) => $e->journal->date->timestamp === $oldestDate);
            $journalDate = $oldestJournal->journal->date;

            $daysOld = abs($asOf->copy()->utc()->startOfDay()->diffInDays(
                $journalDate->copy()->utc()->startOfDay()
            ));
            $bucket = $this->getBucket($daysOld);

            $rows->push(new AgingRow(
                accountId: $accountId,
                accountName: $accountNames[$accountId] ?? 'Unknown',
                journalId: $oldestJournal->journal_id,
                journalDate: $journalDate,
                description: $oldestJournal->journal->description,
                amount: abs($outstanding),
                daysOld: $daysOld,
                bucket: $bucket,
            ));
        }

        // Sort by days old (oldest first)
        return $rows->sortByDesc('daysOld')->values();
    }

    /**
     * Get summary totals per age bucket.
     *
     * @return object{current: int, 31_60: int, 61_90: int, over_90: int, total: int}
     */
    public function summary(): object
    {
        $data = $this->get();

        $current = 0;
        $thirtyOneSixty = 0;
        $sixtyOneNinety = 0;
        $overNinety = 0;

        foreach ($data as $row) {
            match ($row->bucket) {
                'current' => $current += $row->amount,
                '31-60' => $thirtyOneSixty += $row->amount,
                '61-90' => $sixtyOneNinety += $row->amount,
                'over_90' => $overNinety += $row->amount,
            };
        }

        return (object) [
            'current' => $current,
            '31_60' => $thirtyOneSixty,
            '61_90' => $sixtyOneNinety,
            'over_90' => $overNinety,
            'total' => $current + $thirtyOneSixty + $sixtyOneNinety + $overNinety,
        ];
    }

    /**
     * Calculate outstanding amount for journal entries on a specific account.
     *
     * For AR: debit increases, credit decreases → outstanding = debit - credit
     * For AP: credit increases, debit decreases → outstanding = credit - debit
     */
    protected function calculateOutstanding(Collection $entries): int
    {
        $debit = $entries->sum('debit');
        $credit = $entries->sum('credit');

        if ($this->type === AccountSystemKey::AccountsReceivable) {
            return $debit - $credit;
        }

        // Accounts Payable: credit normal balance
        return $credit - $debit;
    }

    /**
     * Get the age bucket label for a given number of days.
     */
    protected function getBucket(int $days): string
    {
        foreach (self::BUCKETS as [$min, $max, $label]) {
            if ($days >= $min && $days <= $max) {
                return $label;
            }
        }

        return 'over_90';
    }

    /**
     * Get all leaf accounts under a system key.
     *
     * Includes the system-key account itself (if leaf) plus any
     * descendant leaf accounts (supports entity sub-accounts).
     */
    protected function getLeafAccountsUnder(AccountSystemKey $key): Collection
    {
        $parentAccount = Account::query()
            ->where('system_key', $key)
            ->first();

        if (! $parentAccount) {
            return new Collection();
        }

        // Get all descendant accounts
        $descendants = $parentAccount->getDescendants();

        // Filter to leaf accounts only
        $leafAccounts = array_filter(
            $descendants,
            fn (Account $account) => $account->isLeaf()
        );

        // If no leaf descendants but parent itself is a leaf, include it
        if (empty($leafAccounts) && $parentAccount->isLeaf()) {
            $leafAccounts[] = $parentAccount;
        }

        return collect($leafAccounts);
    }
}
