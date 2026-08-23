<?php

use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\AccountSnapshot;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\Drivers\ScheduledDriver;
use AloongJerr\Accounting\Snapshots\SnapshotManager;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('resolves scheduled driver via manager', function () {
    $manager = app(SnapshotManager::class);

    $driver = $manager->driver('scheduled');

    expect($driver)->toBeInstanceOf(ScheduledDriver::class);
    expect($driver->getName())->toBe('scheduled');
});

it('generates and stores snapshot in database', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('scheduled');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    // Create a transaction
    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    // Generate snapshot
    $result = $driver->generate(Carbon::today());

    expect($result)->toBeTrue();

    // Verify snapshot was stored
    $snapshot = AccountSnapshot::query()
        ->forDate(Carbon::today())
        ->forTenant(null)
        ->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot->snapshot_type)->toBe('daily');
    expect($snapshot->data)->toBeArray();
    expect($snapshot->data)->not->toBeEmpty();
});

it('retrieves cumulative balances from snapshot', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('scheduled');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    // Create a transaction
    Accounting::received(75000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    // Generate snapshot
    $driver->generate(Carbon::today());

    // Retrieve balances from snapshot
    $balances = $driver->getCumulativeBalances(Carbon::today());

    expect($balances)->not->toBeEmpty();
    expect($balances->first()->accountId)->toBeInt();
    expect($balances->first()->debit)->toBeInt();
    expect($balances->first()->credit)->toBeInt();
    expect($balances->first()->balance)->toBeInt();
});

it('falls back to balance service when no snapshot exists', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('scheduled');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    // Create a transaction
    Accounting::received(30000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    // Retrieve balances WITHOUT generating snapshot first
    $balances = $driver->getCumulativeBalances(Carbon::today());

    // Should fall back to BalanceService and still return data
    expect($balances)->not->toBeEmpty();
});

it('clears snapshots for date range', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('scheduled');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    // Create a transaction
    Accounting::received(10000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    // Generate snapshots for multiple dates
    $driver->generate(Carbon::today());
    $driver->generate(Carbon::yesterday());

    // Verify snapshots exist
    expect(AccountSnapshot::count())->toBe(2);

    // Clear snapshots
    $deleted = $driver->clearSnapshots(Carbon::yesterday(), Carbon::today());

    expect($deleted)->toBe(2);
    expect(AccountSnapshot::count())->toBe(0);
});

it('updates existing snapshot when regenerating', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('scheduled');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    // Create initial transaction
    Accounting::received(10000, 'Test payment 1')
        ->fromAccount($arAccount)
        ->commit();

    // Generate snapshot
    $driver->generate(Carbon::today());

    expect(AccountSnapshot::count())->toBe(1);

    // Create another transaction
    Accounting::received(5000, 'Test payment 2')
        ->fromAccount($arAccount)
        ->commit();

    // Regenerate snapshot (should update, not create new)
    $driver->generate(Carbon::today());

    expect(AccountSnapshot::count())->toBe(1);

    // Verify the snapshot has updated data
    $balances = $driver->getCumulativeBalances(Carbon::today());
    expect($balances)->not->toBeEmpty();
});

it('handles tenant-specific snapshots', function () {
    $driver = app(SnapshotManager::class)->driver('scheduled');

    // Manually create snapshots for different tenants
    $driver->generate(Carbon::today(), 1);
    $driver->generate(Carbon::today(), 2);

    expect(AccountSnapshot::count())->toBe(2);

    // Retrieve balances for tenant 1
    $balances1 = $driver->getCumulativeBalances(Carbon::today(), 1);
    expect($balances1)->toBeInstanceOf(\Illuminate\Support\Collection::class);

    // Retrieve balances for tenant 2
    $balances2 = $driver->getCumulativeBalances(Carbon::today(), 2);
    expect($balances2)->toBeInstanceOf(\Illuminate\Support\Collection::class);

    // Tenant 1 and 2 snapshots should be separate
    $snapshot1 = AccountSnapshot::query()->forDate(Carbon::today())->forTenant(1)->first();
    $snapshot2 = AccountSnapshot::query()->forDate(Carbon::today())->forTenant(2)->first();

    expect($snapshot1)->not->toBeNull();
    expect($snapshot2)->not->toBeNull();
    expect($snapshot1->tenant_id)->toBe(1);
    expect($snapshot2->tenant_id)->toBe(2);
});

it('returns period activity directly from balance service', function () {
    $manager = app(SnapshotManager::class);
    $driver = $manager->driver('scheduled');

    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    // Create transactions
    Accounting::received(10000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    // Get period activity
    $activity = $driver->getPeriodActivity(Carbon::yesterday(), Carbon::today());

    expect($activity)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
