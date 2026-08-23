<?php

use AloongJerr\Accounting\Contracts\AccountingPipe;

it('defines the correct interface methods', function () {
    $reflection = new ReflectionClass(AccountingPipe::class);

    expect($reflection->isInterface())->toBeTrue();
    expect($reflection->hasMethod('handle'))->toBeTrue();

    $method = $reflection->getMethod('handle');
    $params = $method->getParameters();

    expect($params)->toHaveCount(2);
    expect($params[0]->getName())->toBe('transaction');
    expect($params[1]->getName())->toBe('next');
});
