<?php

namespace AloongJerr\Accounting\Snapshots\Drivers;

use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\Contracts\SnapshotDriver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Null snapshot driver.
 *
 * Always calculates balances directly from journal entries.
 * No caching or snapshot storage. Suitable for development
 * or very small datasets.
 */
class NullDriver implements SnapshotDriver
{
    public function __construct(
        protected BalanceService $balanceService,
    ) {}

    public function getCumulativeBalances(Carbon $asOf, ?int $tenantId = null): Collection
    {
        return $this->balanceService->getCumulativeBalances($asOf, $tenantId);
    }

    public function getPeriodActivity(Carbon $from, Carbon $to, ?int $tenantId = null): Collection
    {
        return $this->balanceService->getPeriodActivity($from, $to, $tenantId);
    }

    public function generate(Carbon $periodEnd, ?int $tenantId = null): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'null';
    }
}
