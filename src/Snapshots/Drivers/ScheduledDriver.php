<?php

namespace AloongJerr\Accounting\Snapshots\Drivers;

use AloongJerr\Accounting\Models\AccountSnapshot;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\Contracts\SnapshotDriver;
use AloongJerr\Accounting\ValueObjects\AccountBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Scheduled snapshot driver.
 *
 * Pre-generates account balance snapshots via a scheduled Artisan command
 * and stores them persistently in the database. When queried, looks up
 * the closest pre-generated snapshot instead of calculating from scratch.
 *
 * Ideal for VPS/dedicated servers where cron jobs are available.
 * Provides faster report generation than on-demand calculation for
 * large datasets with many journal entries.
 *
 * Usage:
 *   // Generate daily snapshots via scheduler (automatic)
 *   $schedule->command('accounting:generate-snapshots')->dailyAt('02:00');
 *
 *   // Or generate manually
 *   php artisan accounting:generate-snapshots --date=2024-12-31
 */
class ScheduledDriver implements SnapshotDriver
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

        // No pre-generated snapshot — calculate directly
        return $this->balanceService->getCumulativeBalances($asOf, $tenantId);
    }

    public function getPeriodActivity(Carbon $from, Carbon $to, ?int $tenantId = null): Collection
    {
        // Period activity requires two points in time; calculate directly
        return $this->balanceService->getPeriodActivity($from, $to, $tenantId);
    }

    /**
     * Generate and persist a snapshot for the given date.
     */
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
        return 'scheduled';
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
