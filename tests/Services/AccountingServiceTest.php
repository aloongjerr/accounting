<?php

use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Services\AccountingService;

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

it('throws bad method call for unimplemented transaction types', function () {
    Accounting::received(5000);
})->throws(\BadMethodCallException::class, 'not yet implemented');

it('throws bad method call for paid', function () {
    Accounting::paid(5000);
})->throws(\BadMethodCallException::class, 'not yet implemented');

it('throws bad method call for sold', function () {
    Accounting::sold(5000);
})->throws(\BadMethodCallException::class, 'not yet implemented');

it('throws bad method call for purchased', function () {
    Accounting::purchased(5000);
})->throws(\BadMethodCallException::class, 'not yet implemented');

it('throws bad method call for adjustment', function () {
    Accounting::adjustment(5000);
})->throws(\BadMethodCallException::class, 'not yet implemented');
