<?php

use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Events\JournalManualEvent;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Transactions\ManualJournal;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->resolver = app(AccountResolver::class);
    $this->cash = $this->resolver->resolveSystemAccount(AccountSystemKey::CashOnHand);
    $this->revenue = $this->resolver->resolveSystemAccount(AccountSystemKey::SalesRevenue);
    $this->ar = $this->resolver->resolveSystemAccount(AccountSystemKey::AccountsReceivable);
    $this->ap = $this->resolver->resolveSystemAccount(AccountSystemKey::AccountsPayable);
});

it('creates manual journal via service', function () {
    $journal = Accounting::journal('Contra offset')
        ->debit($this->ap, 500000)
        ->credit($this->ar, 500000)
        ->commit();

    expect($journal)->toBeInstanceOf(Journal::class);
    expect($journal->exists)->toBeTrue();
    expect($journal->description)->toBe('Contra offset');
    expect($journal->status)->toBe(JournalStatus::Draft);
});

it('creates balanced entries', function () {
    $journal = Accounting::journal('Test')
        ->debit($this->cash, 100000)
        ->credit($this->revenue, 100000)
        ->commit();

    expect($journal->entries)->toHaveCount(2);
    expect($journal->totalDebit())->toBe(100000);
    expect($journal->totalCredit())->toBe(100000);
    expect($journal->isBalanced())->toBeTrue();
});

it('supports multiple debit and credit entries', function () {
    $journal = Accounting::journal('Multi entry')
        ->debit($this->cash, 30000)
        ->debit($this->ar, 20000)
        ->credit($this->revenue, 50000)
        ->commit();

    expect($journal->entries)->toHaveCount(3);
    expect($journal->totalDebit())->toBe(50000);
    expect($journal->totalCredit())->toBe(50000);
    expect($journal->isBalanced())->toBeTrue();
});

it('fires JournalManualEvent on commit', function () {
    Event::fake([JournalManualEvent::class]);

    Accounting::journal('Event test')
        ->debit($this->cash, 100000)
        ->credit($this->revenue, 100000)
        ->commit();

    Event::assertDispatched(JournalManualEvent::class);
});

it('supports pipeThrough', function () {
    $pipeRan = false;

    $pipe = new class($pipeRan) implements \AloongJerr\Accounting\Contracts\AccountingPipe
    {
        private bool $flag;

        public function __construct(bool &$flag)
        {
            $this->flag = &$flag;
        }

        public function handle(mixed $transaction, Closure $next): mixed
        {
            $this->flag = true;

            return $next($transaction);
        }
    };

    Accounting::journal('Pipe test')
        ->debit($this->cash, 100000)
        ->credit($this->revenue, 100000)
        ->pipeThrough([$pipe])
        ->commit();

    expect($pipeRan)->toBeTrue();
});

it('supports comments and date', function () {
    $journal = Accounting::journal('Full featured')
        ->debit($this->cash, 100000)
        ->credit($this->revenue, 100000)
        ->comment('Invoice #456')
        ->onDate('2026-01-15')
        ->commit();

    expect($journal->comments)->toBe(['Invoice #456']);
    expect($journal->date->toDateString())->toBe('2026-01-15');
});

it('returns ManualJournal instance from journal()', function () {
    $builder = Accounting::journal('Test');

    expect($builder)->toBeInstanceOf(ManualJournal::class);
});

it('tracks total amount across entries', function () {
    $builder = Accounting::journal('Test')
        ->debit($this->cash, 30000)
        ->credit($this->ar, 20000);

    expect($builder->getAmount())->toBe(50000);
});
