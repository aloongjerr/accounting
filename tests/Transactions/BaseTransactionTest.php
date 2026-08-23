<?php

use AloongJerr\Accounting\Contracts\AccountingPipe;
use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Events\JournalReceivedEvent;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Transactions\BaseTransaction;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
});

// Helper to create a concrete test implementation of BaseTransaction
function createTestTransaction(int $amount, string $desc = 'Test transaction'): BaseTransaction
{
    $resolver = app(AccountResolver::class);

    return new class($amount, $desc, $resolver) extends BaseTransaction
    {
        public function getEventClass(): string
        {
            return JournalReceivedEvent::class;
        }

        public function resolveEntries(): array
        {
            $debitAccount = $this->resolver->resolveSystemAccount(AccountSystemKey::CashOnHand);
            $creditAccount = $this->resolver->resolveSystemAccount(AccountSystemKey::SalesRevenue);

            return [
                ['account_id' => $debitAccount->getKey(), 'debit' => $this->amount, 'credit' => 0],
                ['account_id' => $creditAccount->getKey(), 'debit' => 0, 'credit' => $this->amount],
            ];
        }
    };
}

it('sets amount and description', function () {
    $transaction = createTestTransaction(5000, 'Payment received');

    expect($transaction->getAmount())->toBe(5000);
    expect($transaction->getDescription())->toBe('Payment received');
});

it('defaults date to today', function () {
    $transaction = createTestTransaction(1000);

    expect($transaction->getDate()->isToday())->toBeTrue();
});

it('can set custom date via string', function () {
    $transaction = createTestTransaction(1000)->onDate('2026-01-15');

    expect($transaction->getDate()->toDateString())->toBe('2026-01-15');
});

it('can set custom date via Carbon', function () {
    $date = Carbon::parse('2026-06-01');
    $transaction = createTestTransaction(1000)->onDate($date);

    expect($transaction->getDate()->toDateString())->toBe('2026-06-01');
});

it('can add single comment', function () {
    $transaction = createTestTransaction(1000)->comment('Invoice #123');

    expect($transaction->getComments())->toBe(['Invoice #123']);
});

it('can add multiple comments', function () {
    $transaction = createTestTransaction(1000)
        ->comment('Invoice #123')
        ->comments(['Note 1', 'Note 2']);

    expect($transaction->getComments())->toBe(['Invoice #123', 'Note 1', 'Note 2']);
});

it('can set company id', function () {
    $transaction = createTestTransaction(1000)->forCompany(5);

    expect($transaction->getCompanyId())->toBe(5);
});

it('can add pipes', function () {
    $pipe = new class implements AccountingPipe
    {
        public function handle(mixed $transaction, Closure $next): mixed
        {
            return $next($transaction);
        }
    };

    $transaction = createTestTransaction(1000)->pipeThrough([get_class($pipe)]);

    // Just verify it doesn't throw
    expect($transaction)->toBeInstanceOf(BaseTransaction::class);
});

it('creates journal on commit', function () {
    $journal = createTestTransaction(5000, 'Test commit')->commit();

    expect($journal)->toBeInstanceOf(Journal::class);
    expect($journal->exists)->toBeTrue();
    expect($journal->description)->toBe('Test commit');
    expect($journal->status)->toBe(JournalStatus::Draft);
});

it('creates balanced entries on commit', function () {
    $journal = createTestTransaction(5000)->commit();

    expect($journal->entries)->toHaveCount(2);
    expect($journal->totalDebit())->toBe(5000);
    expect($journal->totalCredit())->toBe(5000);
    expect($journal->isBalanced())->toBeTrue();
});

it('fires event on commit', function () {
    Event::fake([JournalReceivedEvent::class]);

    $journal = createTestTransaction(5000)->commit();

    Event::assertDispatched(JournalReceivedEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

it('runs pipes before commit', function () {
    $pipeRan = false;

    $pipe = new class($pipeRan) implements AccountingPipe
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

    // Pass pipe instance directly (not class name)
    createTestTransaction(5000)
        ->pipeThrough([$pipe])
        ->commit();

    expect($pipeRan)->toBeTrue();
});

it('includes comments in journal', function () {
    $journal = createTestTransaction(5000)
        ->comment('First comment')
        ->comment('Second comment')
        ->commit();

    expect($journal->comments)->toBe(['First comment', 'Second comment']);
});

it('sets currency from config', function () {
    config(['accounting.currency' => 'MYR']);

    $journal = createTestTransaction(5000)->commit();

    expect($journal->currency)->toBe('MYR');
});

it('returns resolver via getter', function () {
    $transaction = createTestTransaction(1000);

    expect($transaction->getResolver())->toBeInstanceOf(AccountResolver::class);
});
