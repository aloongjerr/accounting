<?php

namespace AloongJerr\Accounting\Snapshots\Drivers;

use AloongJerr\Accounting\Models\AccountSnapshot;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\Contracts\SnapshotDriver;
use AloongJerr\Accounting\ValueObjects\AccountBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * On-demand snapshot driver.
 *
 * Calculates balances on the fly and stores them persistently in the
 * account_snapshots table. Subsequent requests for the same date are
 * served from the database. Suitable for shared hosting without cron jobs.
 *
 * Unlike the scheduled driver, snapshots are generated automatically on
 * first request rather than via a scheduled job.
 */
class OnDemandDriver implements SnapshotDriver
{
    public function __construct(
        protected BalanceService $balanceService,
    ) {}

    public function getCumulativeBalances(Carbon $asOf, ?int $tenantId = null): Collection
    {
        $snapshot = AccountSnapshot::query()
            ->forDate($asOf)
            ->forTenant($tenantId)
            ->first();

        if ($snapshot) {
            return $this->reconstructFromSnapshot($snapshot->data);
        }

        // No snapshot exists — calculate and store for future requests
        $this->generate($asOf, $tenantId);

        // Retrieve the newly created snapshot
        $snapshot = AccountSnapshot::query()
            ->forDate($asOf)
            ->forTenant($tenantId)
            ->first();

        return $this->reconstructFromSnapshot($snapshot->data);
    }

    public function getPeriodActivity(Carbon $from, Carbon $to, ?int $tenantId = null): Collection
    {
        // Period activity requires two points in time; calculate directly
        return $this->balanceService->getPeriodActivity($from, $to, $tenantId);
    }

    public function generate(Carbon $periodEnd, ?int $tenantId = null): bool
    {
        $balances = $this->balanceService->getCumulativeBalances($periodEnd, $tenantId);

        $data = [];
        foreach ($balances as $balance) {
            $data[$balance->accountId] = [
                'debit' => $balance->debit,
                'credit' => $balance->credit,
                'balance' => $balance->balance,
            ];
        }

        // Use manual find/update/create to avoid SQLite date comparison issues
        $snapshot = AccountSnapshot::query()
            ->forDate($periodEnd)
            ->forTenant($tenantId)
            ->where('snapshot_type', 'daily')
            ->first();

        if ($snapshot) {
            $snapshot->update(['data' => $data]);
        } else {
            AccountSnapshot::create([
                'snapshot_date' => $periodEnd->toDateString(),
                'tenant_id' => $tenantId,
                'snapshot_type' => 'daily',
                'data' => $data,
            ]);
        }

        return true;
    }

    public function getName(): string
    {
        return 'on_demand';
    }

    /**
     * Delete snapshots for a given date range.
     *
     * Useful for regenerating snapshots after corrections or voids.
     */
    public function clearSnapshots(Carbon $from, Carbon $to, ?int $tenantId = null): int
    {
        $query = AccountSnapshot::query()
            ->whereDate('snapshot_date', '>=', $from)
            ->whereDate('snapshot_date', '<=', $to);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->delete();
    }

    /**
     * Reconstruct AccountBalance value objects from snapshot data.
     *
     * @param  array<string, array{debit: int, credit: int, balance: int}>  $data
     * @return Collection<int, AccountBalance>
     */
    protected function reconstructFromSnapshot(array $data): Collection
    {
        $balances = [];

        foreach ($data as $accountId => $entry) {
            $balances[] = new AccountBalance(
                accountId: (int) $accountId,
                debit: (int) ($entry['debit'] ?? 0),
                credit: (int) ($entry['credit'] ?? 0),
                balance: (int) ($entry['balance'] ?? 0),
            );
        }

        return collect($balances);
    }
}
