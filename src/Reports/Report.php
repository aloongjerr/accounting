<?php

namespace AloongJerr\Accounting\Reports;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Snapshots\SnapshotManager;
use AloongJerr\Accounting\ValueObjects\ReportRow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Abstract base for financial reports.
 *
 * Provides common functionality for date resolution, fiscal year
 * calculation, and account data enrichment.
 */
abstract class Report
{
    protected ?Carbon $asOf = null;

    protected ?Carbon $from = null;

    protected ?Carbon $to = null;

    protected ?int $tenantId = null;

    protected ?SnapshotManager $snapshotManager = null;

    public function __construct()
    {
        $this->snapshotManager = app(SnapshotManager::class);
    }

    /**
     * Set the report date (for point-in-time reports).
     */
    public function asOf(string|Carbon $date): static
    {
        $this->asOf = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $this;
    }

    /**
     * Set the report period (for period reports).
     */
    public function forPeriod(string|Carbon $from, string|Carbon $to): static
    {
        $this->from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $this->to = $to instanceof Carbon ? $to : Carbon::parse($to);

        return $this;
    }

    /**
     * Set the report for a specific fiscal year.
     *
     * Resolves start/end dates from config fiscal_year settings.
     */
    public function forFiscalYear(int $year): static
    {
        $startMonth = (int) Accounting::config('fiscal_year.start_month', 1);
        $startDay = (int) Accounting::config('fiscal_year.start_day', 1);
        $endMonth = (int) Accounting::config('fiscal_year.end_month', 12);
        $endDay = Accounting::config('fiscal_year.end_day');

        $from = Carbon::createFromDate($year, $startMonth, $startDay);

        // Determine the end year based on fiscal year structure
        $endYear = ($endMonth >= $startMonth) ? $year : $year + 1;

        // If end_day is null, use last day of the month
        if ($endDay === null) {
            $to = Carbon::createFromDate($endYear, $endMonth, 1)->endOfMonth();
        } else {
            $to = Carbon::createFromDate($endYear, $endMonth, (int) $endDay);
        }

        $this->from = $from;
        $this->to = $to;
        $this->asOf = $to;

        return $this;
    }

    /**
     * Set the tenant filter.
     */
    public function forTenant(?int $tenantId): static
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    /**
     * Generate the report data.
     */
    abstract public function get(): Collection;

    /**
     * Enrich balance data with account details.
     *
     * Takes raw balance data (AccountBalance or AccountActivity value objects)
     * and adds account name, code, type, system_key, and parent info.
     *
     * @param  Collection<int, \AloongJerr\Accounting\ValueObjects\AccountBalance|\AloongJerr\Accounting\ValueObjects\AccountActivity>  $balances
     * @return Collection<int, ReportRow>
     */
    protected function enrichWithAccountDetails(Collection $balances): Collection
    {
        $accountIds = $balances->map(fn ($b) => $b->accountId)->all();

        if (empty($accountIds)) {
            return new Collection();
        }

        $accounts = Account::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        return $balances->map(function ($balance) use ($accounts) {
            $account = $accounts->get($balance->accountId);

            if (! $account) {
                return null;
            }

            return ReportRow::fromAccount(
                $account,
                debit: $balance->debit,
                credit: $balance->credit,
                balance: $balance->balance,
            );
        })->filter()->values();
    }

    /**
     * Roll up leaf account balances to their parent accounts.
     *
     * Takes enriched balance data and aggregates balances up the
     * account hierarchy (leaf → category → group).
     *
     * @param  Collection<int, ReportRow>  $enrichedBalances
     * @return Collection<int, ReportRow>
     */
    protected function rollupBalances(Collection $enrichedBalances): Collection
    {
        // Build a lookup of all accounts
        $accountIds = $enrichedBalances->map(fn ($r) => $r->accountId)->all();
        $parentIds = $enrichedBalances->map(fn ($r) => $r->parentId)->filter()->unique()->all();
        $allIds = array_unique(array_merge($accountIds, $parentIds));

        $allAccounts = Account::query()
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        // Create balance map
        /** @var array<int, ReportRow> $rolled */
        $rolled = [];
        foreach ($enrichedBalances as $item) {
            // Add leaf balance
            $rolled[$item->accountId] = $item;

            // Walk up the hierarchy
            $parentId = $item->parentId;
            while ($parentId) {
                $parent = $allAccounts->get($parentId);
                if (! $parent) {
                    break;
                }

                if (! isset($rolled[$parent->getKey()])) {
                    $rolled[$parent->getKey()] = ReportRow::fromAccount($parent);
                }

                $rolled[$parent->getKey()]->add($item->debit, $item->credit, $item->balance);

                $parentId = $parent->parent_id;
            }
        }

        return collect(array_values($rolled))
            ->sortBy(fn (ReportRow $row) => $row->accountCode)
            ->values();
    }
}
