<?php

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\AccountingConfiguration;

beforeEach(function () {
    // Reset tenant to null before each test
    app(AccountingConfiguration::class)->tenant(null);
});

it('resolves accounting configuration as singleton', function () {
    $first = app(AccountingConfiguration::class);
    $second = app(AccountingConfiguration::class);

    expect($first)->toBe($second);
});

it('can set tenant via configuration', function () {
    $config = app(AccountingConfiguration::class);
    $config->tenant(5);

    expect($config->getTenant())->toBe(5);
});

it('can set null tenant via configuration', function () {
    $config = app(AccountingConfiguration::class);
    $config->tenant(5);
    $config->tenant(null);

    expect($config->getTenant())->toBeNull();
});

it('returns fluent interface from tenant method', function () {
    $config = app(AccountingConfiguration::class);
    $result = $config->tenant(3);

    expect($result)->toBe($config);
});

it('can set tenant via static helper', function () {
    Accounting::setTenant(10);

    expect(Accounting::getTenant())->toBe(10);
});

it('can get null tenant by default', function () {
    expect(Accounting::getTenant())->toBeNull();
});

it('persists tenant across calls', function () {
    Accounting::setTenant(7);

    // Multiple calls should return same value
    expect(Accounting::getTenant())->toBe(7);
    expect(Accounting::getTenant())->toBe(7);
});

it('can change tenant', function () {
    Accounting::setTenant(1);
    expect(Accounting::getTenant())->toBe(1);

    Accounting::setTenant(2);
    expect(Accounting::getTenant())->toBe(2);

    Accounting::setTenant(null);
    expect(Accounting::getTenant())->toBeNull();
});
