<?php

use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Services\AccountingService;
use AloongJerr\Accounting\Transactions\AdjustmentTransaction;
use AloongJerr\Accounting\Transactions\ManualJournal;
use AloongJerr\Accounting\Transactions\PaidTransaction;
use AloongJerr\Accounting\Transactions\PurchasedTransaction;
use AloongJerr\Accounting\Transactions\ReceivedTransaction;
use AloongJerr\Accounting\Transactions\SoldTransaction;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
});

it('resolves accounting service from container', function () {
    $service = app(AccountingService::class);

    expect($service)->toBeInstanceOf(AccountingService::class);
});

it('resolves as singleton', function () {
    $first = app(AccountingService::class);
    $second = app(AccountingService::class);

    expect($first)->toBe($second);
});

it('has resolver accessor', function () {
    $service = app(AccountingService::class);

    expect($service->resolver())->toBeInstanceOf(AccountResolver::class);
});

it('facade resolves to accounting service', function () {
    expect(Accounting::resolver())->toBeInstanceOf(AccountResolver::class);
});

it('returns ManualJournal from journal()', function () {
    expect(Accounting::journal('test'))->toBeInstanceOf(ManualJournal::class);
});

it('returns ReceivedTransaction from received()', function () {
    expect(Accounting::received(5000, 'test'))->toBeInstanceOf(ReceivedTransaction::class);
});

it('returns PaidTransaction from paid()', function () {
    expect(Accounting::paid(5000, 'test'))->toBeInstanceOf(PaidTransaction::class);
});

it('returns SoldTransaction from sold()', function () {
    expect(Accounting::sold(5000, 'test'))->toBeInstanceOf(SoldTransaction::class);
});

it('returns PurchasedTransaction from purchased()', function () {
    expect(Accounting::purchased(5000, 'test'))->toBeInstanceOf(PurchasedTransaction::class);
});

it('returns AdjustmentTransaction from adjustment()', function () {
    expect(Accounting::adjustment(5000, 'test'))->toBeInstanceOf(AdjustmentTransaction::class);
});
