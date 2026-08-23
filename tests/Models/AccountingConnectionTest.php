<?php

use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\AccountSnapshot;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Models\JournalEntry;

it('uses default connection when config is null', function () {
    config(['accounting.connection' => null]);

    $account = new Account();
    $journal = new Journal();
    $journalEntry = new JournalEntry();
    $snapshot = new AccountSnapshot();

    // When config is null, models use their default connection (null = app default)
    expect($account->getConnectionName())->toBeNull();
    expect($journal->getConnectionName())->toBeNull();
    expect($journalEntry->getConnectionName())->toBeNull();
    expect($snapshot->getConnectionName())->toBeNull();
});

it('uses custom connection when config is set', function () {
    config(['accounting.connection' => 'accounting_test']);

    $account = new Account();
    $journal = new Journal();
    $journalEntry = new JournalEntry();
    $snapshot = new AccountSnapshot();

    expect($account->getConnectionName())->toBe('accounting_test');
    expect($journal->getConnectionName())->toBe('accounting_test');
    expect($journalEntry->getConnectionName())->toBe('accounting_test');
    expect($snapshot->getConnectionName())->toBe('accounting_test');

    // Reset config
    config(['accounting.connection' => null]);
});
