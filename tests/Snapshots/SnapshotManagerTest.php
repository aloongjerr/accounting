<?php

use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\Contracts\SnapshotDriver;
use AloongJerr\Accounting\Snapshots\Drivers\NullDriver;
use AloongJerr\Accounting\Snapshots\Drivers\OnDemandDriver;
use AloongJerr\Accounting\Snapshots\Drivers\ScheduledDriver;
use AloongJerr\Accounting\Snapshots\SnapshotManager;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('resolves default snapshot driver', function () {
    $manager = app(SnapshotManager::class);

    expect($manager->driver())->toBeInstanceOf(SnapshotDriver::class);
    expect($manager->driver()->getName())->toBe('on_demand');
});

it('resolves null driver', function () {
    $manager = app(SnapshotManager::class);

    $driver = $manager->driver('null');

    expect($driver)->toBeInstanceOf(NullDriver::class);
    expect($driver->getName())->toBe('null');
});

it('resolves on_demand driver', function () {
    $manager = app(SnapshotManager::class);

    $driver = $manager->driver('on_demand');

    expect($driver)->toBeInstanceOf(OnDemandDriver::class);
    expect($driver->getName())->toBe('on_demand');
});

it('resolves scheduled driver', function () {
    $manager = app(SnapshotManager::class);

    $driver = $manager->driver('scheduled');

    expect($driver)->toBeInstanceOf(ScheduledDriver::class);
    expect($driver->getName())->toBe('scheduled');
});

it('throws for unsupported driver', function () {
    $manager = app(SnapshotManager::class);

    $manager->driver('nonexistent');
})->throws(InvalidArgumentException::class);

it('supports custom driver via extend', function () {
    $manager = app(SnapshotManager::class);

    $manager->extend('custom', function (BalanceService $balanceService) {
        return new NullDriver($balanceService);
    });

    $driver = $manager->driver('custom');

    expect($driver)->toBeInstanceOf(NullDriver::class);
});

it('caches driver instances', function () {
    $manager = app(SnapshotManager::class);

    $driver1 = $manager->driver('null');
    $driver2 = $manager->driver('null');

    expect($driver1)->toBe($driver2);
});

it('null driver returns empty balances when no entries', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('null');

    $balances = $driver->getCumulativeBalances(\Carbon\Carbon::today());

    expect($balances)->toBeEmpty();
});

it('null driver returns balances after transactions', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('null');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)->first();

    // Create a transaction
    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    $balances = $driver->getCumulativeBalances(\Carbon\Carbon::today());

    expect($balances)->not->toBeEmpty();
});

it('null driver generate returns false', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('null');

    expect($driver->generate(\Carbon\Carbon::today()))->toBeFalse();
});

it('on_demand driver generate returns true', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('on_demand');

    expect($driver->generate(\Carbon\Carbon::today()))->toBeTrue();
});

it('snapshot manager forwards calls to default driver', function () {
    $manager = app(SnapshotManager::class);

    $balances = $manager->getCumulativeBalances(\Carbon\Carbon::today());

    expect($balances)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('balance service is available via facade', function () {
    $service = Accounting::balanceService();

    expect($service)->toBeInstanceOf(BalanceService::class);
});

it('snapshot manager is available via facade', function () {
    $manager = Accounting::snapshot();

    expect($manager)->toBeInstanceOf(SnapshotManager::class);
});
