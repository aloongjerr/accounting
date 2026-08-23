<?php

namespace AloongJerr\Accounting;

use Closure;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Fluent configuration builder for the accounting package.
 *
 * Allows users to configure the accounting package in their AppServiceProvider
 * using a fluent interface.
 *
 * Example:
 * Accounting::configure(function ($accounting) {
 *     $accounting->currency('MYR')
 *                ->fiscalYear(startMonth: 1, startDay: 1, endMonth: 12, endDay: null)
 *                ->snapshot(driver: 'scheduled', scheduleTime: '00:10')
 *                ->schedule(function ($schedule) {
 *                    $schedule->command('accounting:generate-snapshots')
 *                             ->dailyAt('00:10')
 *                             ->evenInMaintenanceMode();
 *                });
 * });
 */
class AccountingConfiguration
{
    /**
     * Configuration values to be merged with the default config.
     */
    protected array $config = [];

    /**
     * Schedule configuration callback.
     */
    protected ?Closure $scheduleCallback = null;

    /**
     * Set the default currency.
     */
    public function currency(string $currency): self
    {
        $this->config['currency'] = $currency;
        return $this;
    }

    /**
     * Set the database connection for accounting tables.
     */
    public function connection(?string $connection): self
    {
        $this->config['connection'] = $connection;
        return $this;
    }

    /**
     * Set the fiscal year configuration.
     */
    public function fiscalYear(
        int $startMonth = 1,
        int $startDay = 1,
        int $endMonth = 12,
        ?int $endDay = null
    ): self {
        $this->config['fiscal_year'] = [
            'start_month' => $startMonth,
            'start_day' => $startDay,
            'end_month' => $endMonth,
            'end_day' => $endDay,
        ];
        return $this;
    }

    /**
     * Set the snapshot driver and optional schedule time.
     */
    public function snapshot(string $driver, ?string $scheduleTime = null): self
    {
        $this->config['snapshot'] = [
            'driver' => $driver,
        ];
        
        if ($scheduleTime !== null) {
            $this->config['snapshot']['schedule_time'] = $scheduleTime;
        }
        
        return $this;
    }

    /**
     * Set the immutability policy.
     */
    public function immutable(bool $immutable = true): self
    {
        $this->config['immutable'] = $immutable;
        return $this;
    }

    /**
     * Add custom account system keys.
     */
    public function accountKeys(array $keys): self
    {
        $this->config['account_keys'] = $keys;
        return $this;
    }

    /**
     * Configure scheduled tasks for the accounting package.
     *
     * @param Closure(Schedule): void $callback
     */
    public function schedule(Closure $callback): self
    {
        $this->scheduleCallback = $callback;
        return $this;
    }

    /**
     * Get the schedule configuration callback.
     */
    public function getScheduleCallback(): ?Closure
    {
        return $this->scheduleCallback;
    }

    /**
     * Apply the configuration to the Laravel config repository.
     */
    public function apply(): void
    {
        $currentConfig = config('accounting', []);
        $mergedConfig = array_merge($currentConfig, $this->config);

        config(['accounting' => $mergedConfig]);
    }
}
