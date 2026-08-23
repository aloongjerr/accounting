<?php

namespace AloongJerr\Accounting\Snapshots;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\Contracts\SnapshotDriver;
use AloongJerr\Accounting\Snapshots\Drivers\NullDriver;
use AloongJerr\Accounting\Snapshots\Drivers\OnDemandDriver;
use AloongJerr\Accounting\Snapshots\Drivers\ScheduledDriver;
use InvalidArgumentException;

/**
 * Manages snapshot drivers.
 *
 * Similar to Laravel's CacheManager, this class resolves and manages
 * snapshot drivers based on configuration.
 *
 * Usage:
 *   $manager = app(SnapshotManager::class);
 *   $driver = $manager->driver(); // default driver
 *   $balances = $driver->getCumulativeBalances($asOf);
 */
class SnapshotManager
{
    /**
     * The resolved driver instances.
     *
     * @var array<string, SnapshotDriver>
     */
    protected array $drivers = [];

    /**
     * Custom driver creators.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    public function __construct(
        protected BalanceService $balanceService,
    ) {}

    /**
     * Get a snapshot driver instance.
     */
    public function driver(?string $name = null): SnapshotDriver
    {
        $name = $name ?? $this->getDefaultDriver();

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Register a custom driver creator.
     */
    public function extend(string $name, callable $creator): static
    {
        $this->customCreators[$name] = $creator;

        return $this;
    }

    /**
     * Get the default snapshot driver name.
     */
    public function getDefaultDriver(): string
    {
        return Accounting::config('snapshot.driver', 'on_demand');
    }

    /**
     * Resolve a driver by name.
     */
    protected function resolve(string $name): SnapshotDriver
    {
        // Check custom creators first
        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this->balanceService);
        }

        return match ($name) {
            'null' => new NullDriver($this->balanceService),
            'on_demand' => new OnDemandDriver($this->balanceService),
            'scheduled' => new ScheduledDriver($this->balanceService),
            default => throw new InvalidArgumentException("Snapshot driver [{$name}] is not supported."),
        };
    }

    /**
     * Forward calls to the default driver.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->{$method}(...$parameters);
    }
}
