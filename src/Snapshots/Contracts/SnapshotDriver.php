<?php

namespace AloongJerr\Accounting\Snapshots\Contracts;

use AloongJerr\Accounting\ValueObjects\AccountActivity;
use AloongJerr\Accounting\ValueObjects\AccountBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Contract for snapshot drivers.
 *
 * Snapshot drivers determine how account balances are retrieved
 * or cached for report generation. Different drivers can optimize
 * for different hosting environments (shared hosting vs VPS).
 */
interface SnapshotDriver
{
    /**
     * Get cumulative balances for all leaf accounts as of a date.
     *
     * @return Collection<int, AccountBalance>
     */
    public function getCumulativeBalances(Carbon $asOf, ?int $tenantId = null): Collection;

    /**
     * Get period activity for all leaf accounts within a date range.
     *
     * @return Collection<int, AccountActivity>
     */
    public function getPeriodActivity(Carbon $from, Carbon $to, ?int $tenantId = null): Collection;

    /**
     * Generate snapshot for a period end date.
     *
     * Returns true if snapshot was generated, false if not supported.
     */
    public function generate(Carbon $periodEnd, ?int $tenantId = null): bool;

    /**
     * Get the driver name.
     */
    public function getName(): string;
}
