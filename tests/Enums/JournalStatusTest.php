<?php

use AloongJerr\Accounting\Enums\JournalStatus;
use Filament\Support\Contracts\HasLabel;

it('has correct cases', function () {
    expect(JournalStatus::cases())->toHaveCount(4);
    expect(JournalStatus::Draft->value)->toBe('draft');
    expect(JournalStatus::Posted->value)->toBe('posted');
    expect(JournalStatus::Void->value)->toBe('void');
    expect(JournalStatus::Reversed->value)->toBe('reversed');
});

it('implements HasLabel contract', function () {
    expect(JournalStatus::Draft)->toBeInstanceOf(HasLabel::class);
});

it('returns label via translation', function () {
    $label = JournalStatus::Draft->getLabel();

    expect($label)->toBeString();
    expect($label)->not->toBeEmpty();
});

it('only draft is editable', function () {
    expect(JournalStatus::Draft->isEditable())->toBeTrue();
    expect(JournalStatus::Posted->isEditable())->toBeFalse();
    expect(JournalStatus::Void->isEditable())->toBeFalse();
    expect(JournalStatus::Reversed->isEditable())->toBeFalse();
});

it('void and reversed are final', function () {
    expect(JournalStatus::Draft->isFinal())->toBeFalse();
    expect(JournalStatus::Posted->isFinal())->toBeFalse();
    expect(JournalStatus::Void->isFinal())->toBeTrue();
    expect(JournalStatus::Reversed->isFinal())->toBeTrue();
});

it('can be created from value', function () {
    expect(JournalStatus::from('draft'))->toBe(JournalStatus::Draft);
    expect(JournalStatus::from('posted'))->toBe(JournalStatus::Posted);
    expect(JournalStatus::from('void'))->toBe(JournalStatus::Void);
    expect(JournalStatus::from('reversed'))->toBe(JournalStatus::Reversed);
});

it('throws on invalid value', function () {
    JournalStatus::from('invalid');
})->throws(ValueError::class);
