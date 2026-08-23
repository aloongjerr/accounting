<?php

use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\AccountSnapshot;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('generates snapshot for today via command', function () {
    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    $this->artisan('accounting:generate-snapshots')
        ->assertSuccessful();

    expect(AccountSnapshot::count())->toBe(1);
    expect(AccountSnapshot::first()->snapshot_date->toDateString())
        ->toBe(Carbon::today()->toDateString());
});

it('generates snapshot for specific date', function () {
    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    $this->artisan('accounting:generate-snapshots', ['--date' => '2024-12-31'])
        ->assertSuccessful();

    expect(AccountSnapshot::count())->toBe(1);
    expect(AccountSnapshot::first()->snapshot_date->toDateString())
        ->toBe('2024-12-31');
});

it('generates snapshots for date range', function () {
    $arAccount = \AloongJerr\Accounting\Models\Account::query()
        ->where('system_key', \AloongJerr\Accounting\Enums\AccountSystemKey::AccountsReceivable)
        ->first();

    Accounting::received(50000, 'Test payment')
        ->fromAccount($arAccount)
        ->commit();

    $this->artisan('accounting:generate-snapshots', [
        '--from' => '2024-12-29',
        '--to' => '2024-12-31',
    ])->assertSuccessful();

    // Should generate 3 snapshots (29, 30, 31)
    expect(AccountSnapshot::count())->toBe(3);
});
