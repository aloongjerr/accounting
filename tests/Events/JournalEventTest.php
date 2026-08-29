<?php

use AloongJerr\Accounting\Events\JournalAdjustmentEvent;
use AloongJerr\Accounting\Events\JournalEvent;
use AloongJerr\Accounting\Events\JournalManualEvent;
use AloongJerr\Accounting\Events\JournalPaidEvent;
use AloongJerr\Accounting\Events\JournalPurchasedEvent;
use AloongJerr\Accounting\Events\JournalReceivedEvent;
use AloongJerr\Accounting\Events\JournalSoldEvent;
use AloongJerr\Accounting\Events\JournalTransferredEvent;

it('has JournalReceivedEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalReceivedEvent::class, JournalEvent::class))->toBeTrue();
});

it('has JournalPaidEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalPaidEvent::class, JournalEvent::class))->toBeTrue();
});

it('has JournalSoldEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalSoldEvent::class, JournalEvent::class))->toBeTrue();
});

it('has JournalPurchasedEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalPurchasedEvent::class, JournalEvent::class))->toBeTrue();
});

it('has JournalAdjustmentEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalAdjustmentEvent::class, JournalEvent::class))->toBeTrue();
});

it('has JournalManualEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalManualEvent::class, JournalEvent::class))->toBeTrue();
});

it('has JournalTransferredEvent extending JournalEvent', function () {
    expect(is_subclass_of(JournalTransferredEvent::class, JournalEvent::class))->toBeTrue();
});
