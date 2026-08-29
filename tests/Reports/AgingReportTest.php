<?php

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Reports\AgingReport;
use AloongJerr\Accounting\ValueObjects\AgingRow;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder::class);
});

it('returns empty aging report when no transactions', function () {
    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf(Carbon::today())
        ->get();

    expect($data)->toBeEmpty();
});

it('shows outstanding receivables after sold transaction', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    $journal = Accounting::sold(500000, 'Sale to Customer')
        ->toAccount($arAccount)
        ->commit();
    $journal->post();

    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf(Carbon::today())
        ->get();

    expect($data)->toHaveCount(1);
    expect($data->first())->toBeInstanceOf(AgingRow::class);
    expect($data->first()->amount)->toBe(500000);
    expect($data->first()->bucket)->toBe('current');
});

it('shows outstanding payables after purchased transaction', function () {
    $apAccount = Account::query()->where('system_key', AccountSystemKey::AccountsPayable)->first();

    $journal = Accounting::purchased(300000, 'Purchase from Supplier')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->fromAccount($apAccount)
        ->commit();
    $journal->post();

    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsPayable)
        ->asOf(Carbon::today())
        ->get();

    expect($data)->toHaveCount(1);
    expect($data->first()->amount)->toBe(300000);
});

it('reduces outstanding when partial payment received', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    $journal = Accounting::sold(500000, 'Sale to Customer')
        ->toAccount($arAccount)
        ->commit();
    $journal->post();

    $journal2 = Accounting::received(200000, 'Partial payment')
        ->fromAccount($arAccount)
        ->commit();
    $journal2->post();

    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf(Carbon::today())
        ->get();

    // Should show reduced outstanding (500000 - 200000 = 300000)
    expect($data)->toHaveCount(1);
    expect($data->first()->amount)->toBe(300000);
});

it('removes row when fully settled', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    $journal = Accounting::sold(500000, 'Sale to Customer')
        ->toAccount($arAccount)
        ->commit();
    $journal->post();

    $journal2 = Accounting::received(500000, 'Full payment')
        ->fromAccount($arAccount)
        ->commit();
    $journal2->post();

    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf(Carbon::today())
        ->get();

    expect($data)->toBeEmpty();
});

it('categorizes by age buckets correctly', function () {
    $arParent = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();
    $today = Carbon::today();

    // Create separate AR child accounts with different dates
    $bucketConfig = [
        ['Recent Customer', 'AR-RCNT', $today->copy()->subDays(10)],
        ['Medium Customer', 'AR-MEDM', $today->copy()->subDays(45)],
        ['Old Customer', 'AR-OLD', $today->copy()->subDays(75)],
        ['Very Old Customer', 'AR-VOLD', $today->copy()->subDays(120)],
    ];

    foreach ($bucketConfig as [$name, $code, $date]) {
        $childAccount = Account::query()->create([
            'name' => $name,
            'code' => $code,
            'type' => AccountType::Account,
            'parent_id' => $arParent->id,
            'is_active' => true,
        ]);

        $j = Accounting::sold(100000, "Sale to {$name}")
            ->toAccount($childAccount)
            ->onDate($date)
            ->commit();
        $j->post();
    }

    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf($today)
        ->get();

    // Should have one row per child account
    expect($data)->toHaveCount(4);

    // All rows should have valid bucket labels
    $validBuckets = ['current', '31-60', '61-90', 'over_90'];
    foreach ($data as $row) {
        expect($validBuckets)->toContain($row->bucket);
        expect($row->daysOld)->toBeGreaterThanOrEqual(0);
        expect($row->amount)->toBe(100000);
    }

    // Rows should be sorted by daysOld descending (oldest first)
    $daysOldValues = $data->pluck('daysOld')->toArray();
    for ($i = 1; $i < count($daysOldValues); $i++) {
        expect($daysOldValues[$i - 1])->toBeGreaterThanOrEqual($daysOldValues[$i]);
    }
});

it('summary returns correct totals', function () {
    $arParent = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();
    $today = Carbon::today();

    $bucketConfig = [
        ['Recent', 'AR-SUM-CUR', 100000, $today->copy()->subDays(5)],
        ['Medium', 'AR-SUM-MED', 200000, $today->copy()->subDays(40)],
        ['Old', 'AR-SUM-OLD', 300000, $today->copy()->subDays(100)],
    ];

    foreach ($bucketConfig as [$name, $code, $amount, $date]) {
        $childAccount = Account::query()->create([
            'name' => $name,
            'code' => $code,
            'type' => AccountType::Account,
            'parent_id' => $arParent->id,
            'is_active' => true,
        ]);

        $j = Accounting::sold($amount, "Sale to {$name}")
            ->toAccount($childAccount)
            ->onDate($date)
            ->commit();
        $j->post();
    }

    $report = new AgingReport();
    $summary = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf($today)
        ->summary();

    // Total should be sum of all outstanding amounts
    expect($summary->total)->toBe(600000);

    // Individual buckets should sum to total
    $bucketSum = $summary->current + $summary->{'31_60'} + $summary->{'61_90'} + $summary->over_90;
    expect($bucketSum)->toBe($summary->total);
});

it('defaults to accounts receivable type', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    $journal = Accounting::sold(100000, 'Sale')
        ->toAccount($arAccount)
        ->commit();
    $journal->post();

    $report = new AgingReport();
    $data = $report->asOf(Carbon::today())->get();

    expect($data)->toHaveCount(1);
});

it('creates via facade', function () {
    $report = Accounting::aging();

    expect($report)->toBeInstanceOf(AgingReport::class);
});

it('respects asOf date filter', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();
    $today = Carbon::today();

    // Create a sale dated 10 days ago (recent)
    $journal = Accounting::sold(500000, 'Recent sale')
        ->toAccount($arAccount)
        ->onDate($today->copy()->subDays(10))
        ->commit();
    $journal->post();

    // Report as of 30 days ago — journal dated 10 days ago is AFTER asOf, so excluded
    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf($today->copy()->subDays(30))
        ->get();

    expect($data)->toBeEmpty();
});

it('only includes posted journals', function () {
    $arAccount = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    $journal = Accounting::sold(500000, 'Sale')
        ->toAccount($arAccount)
        ->commit();
    $journal->post();

    // Create a draft journal manually (no entries, so won't affect query)
    Journal::query()->create([
        'date' => Carbon::today(),
        'description' => 'Draft journal',
        'status' => \AloongJerr\Accounting\Enums\JournalStatus::Draft,
    ]);

    $report = new AgingReport();
    $data = $report
        ->forType(AccountSystemKey::AccountsReceivable)
        ->asOf(Carbon::today())
        ->get();

    expect($data)->toHaveCount(1);
    expect($data->first()->description)->toBe('Sale');
});
