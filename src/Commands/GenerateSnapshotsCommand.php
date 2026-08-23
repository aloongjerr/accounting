<?php

namespace AloongJerr\Accounting\Commands;

use AloongJerr\Accounting\Snapshots\SnapshotManager;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

/**
 * Generate account balance snapshots.
 *
 * Pre-generates cumulative balance snapshots for the scheduled driver.
 * Can be run manually or scheduled via Laravel's task scheduler.
 *
 * Usage:
 *   php artisan accounting:generate-snapshots                    # Today
 *   php artisan accounting:generate-snapshots --date=2024-12-31  # Specific date
 *   php artisan accounting:generate-snapshots --from=2024-01-01 --to=2024-12-31
 *   php artisan accounting:generate-snapshots --tenant=1
 */
class GenerateSnapshotsCommand extends Command
{
    protected $signature = 'accounting:generate-snapshots
        {--date= : Snapshot date (default: today)}
        {--from= : Start date for range generation}
        {--to= : End date for range generation (default: today)}
        {--tenant= : Tenant ID to generate snapshots for}';

    protected $description = 'Generate account balance snapshots for the scheduled driver';

    public function handle(SnapshotManager $manager): int
    {
        $driver = $manager->driver('scheduled');
        $tenantId = $this->option('tenant') !== null ? (int) $this->option('tenant') : null;

        $dates = $this->resolveDates();

        $this->components->info('Generating snapshots for ' . count($dates) . ' date(s)...');

        foreach ($dates as $date) {
            $driver->generate($date, $tenantId);

            $this->components->task(
                "Snapshot for {$date->toDateString()}" . ($tenantId ? " (tenant: {$tenantId})" : '')
            );
        }

        $this->newLine();
        $this->components->info('Done. ' . count($dates) . ' snapshot(s) generated.');

        return self::SUCCESS;
    }

    /**
     * Resolve the dates to generate snapshots for.
     *
     * @return array<Carbon>
     */
    protected function resolveDates(): array
    {
        if ($this->option('from')) {
            $from = Carbon::parse($this->option('from'));
            $to = $this->option('to')
                ? Carbon::parse($this->option('to'))
                : Carbon::today();

            return iterator_to_array(CarbonPeriod::create($from, $to));
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        return [$date];
    }
}
