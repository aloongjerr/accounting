<?php

use AloongJerr\Accounting\Traits\HasCurrency;

// Create a test class that uses the trait
$makeClass = function () {
    return new class {
        use HasCurrency;

        public function testResolveCurrency(?int $companyId = null): string
        {
            return $this->resolveCurrency($companyId);
        }
    };
};

it('returns default currency from config', function () use ($makeClass) {
    config(['accounting.currency' => 'MYR']);

    $instance = $makeClass();

    expect($instance->testResolveCurrency())->toBe('MYR');
});

it('returns config currency regardless of company_id', function () use ($makeClass) {
    config(['accounting.currency' => 'USD']);

    $instance = $makeClass();

    // Future: company_id will fetch from companies table
    // Currently always returns config default
    expect($instance->testResolveCurrency(1))->toBe('USD');
    expect($instance->testResolveCurrency(null))->toBe('USD');
});
