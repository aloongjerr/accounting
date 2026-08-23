<?php

use AloongJerr\Accounting\Contracts\Accountable;
use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Events\JournalAdjustmentEvent;
use AloongJerr\Accounting\Events\JournalPaidEvent;
use AloongJerr\Accounting\Events\JournalPurchasedEvent;
use AloongJerr\Accounting\Events\JournalReceivedEvent;
use AloongJerr\Accounting\Events\JournalSoldEvent;
use AloongJerr\Accounting\Facades\Accounting;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Resolvers\AccountResolver;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
});

function makeCustomer(int $id = 1, string $name = 'Test Customer'): Accountable
{
    return new class($id, $name) implements Accountable
    {
        public function __construct(protected int $id, protected string $name) {}

        public function getAccountKeys(): BackedEnum|array|\AloongJerr\Accounting\Enums\AccountSystemKey
        {
            return AccountSystemKey::AccountsReceivable;
        }

        public function getAccountIdentifier(): array
        {
            return ['id' => $this->id, 'name' => $this->name];
        }
    };
}

function makeSupplier(int $id = 1, string $name = 'Test Supplier'): Accountable
{
    return new class($id, $name) implements Accountable
    {
        public function __construct(protected int $id, protected string $name) {}

        public function getAccountKeys(): BackedEnum|array|\AloongJerr\Accounting\Enums\AccountSystemKey
        {
            return AccountSystemKey::AccountsPayable;
        }

        public function getAccountIdentifier(): array
        {
            return ['id' => $this->id, 'name' => $this->name];
        }
    };
}

// ── ReceivedTransaction ──

it('received: creates balanced entries with from entity and toCash', function () {
    $journal = Accounting::received(500000, 'Customer payment')
        ->from(makeCustomer())
        ->toCash()
        ->commit();

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebit())->toBe(500000);
    expect($journal->totalCredit())->toBe(500000);
    expect($journal->entries)->toHaveCount(2);
    expect($journal->status)->toBe(JournalStatus::Draft);
});

it('received: debits cash on hand by default', function () {
    $journal = Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    expect($debitEntry->account_id)->toBe($cashAccount->getKey());
});

it('received: debits bank when toBank called', function () {
    $journal = Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->toBank()
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    expect($debitEntry->account_id)->toBe($bankAccount->getKey());
});

it('received: credits entity AR account via from()', function () {
    $customer = makeCustomer(42, 'Acme Corp');
    $journal = Accounting::received(500000, 'Payment')
        ->from($customer)
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $resolver = app(AccountResolver::class);
    $expectedAccount = $resolver->resolveEntityAccount($customer, AccountSystemKey::AccountsReceivable);

    expect($creditEntry->account_id)->toBe($expectedAccount->getKey());
});

it('received: uses fromAccount for explicit credit', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::AccountsReceivable)->first();

    $journal = Accounting::received(500000, 'Payment')
        ->fromAccount($account)
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    expect($creditEntry->account_id)->toBe($account->getKey());
});

it('received: throws without source', function () {
    Accounting::received(500000, 'Payment')->commit();
})->throws(\LogicException::class, 'requires a source');

it('received: fires JournalReceivedEvent', function () {
    Event::fake([JournalReceivedEvent::class]);

    $journal = Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->commit();

    Event::assertDispatched(JournalReceivedEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

// ── PaidTransaction ──

it('paid: creates balanced entries with to entity and fromCash', function () {
    $journal = Accounting::paid(300000, 'Supplier payment')
        ->to(makeSupplier())
        ->fromCash()
        ->commit();

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebit())->toBe(300000);
    expect($journal->totalCredit())->toBe(300000);
    expect($journal->entries)->toHaveCount(2);
});

it('paid: credits cash on hand by default', function () {
    $journal = Accounting::paid(300000, 'Payment')
        ->to(makeSupplier())
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    expect($creditEntry->account_id)->toBe($cashAccount->getKey());
});

it('paid: credits bank when fromBank called', function () {
    $journal = Accounting::paid(300000, 'Payment')
        ->to(makeSupplier())
        ->fromBank()
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    expect($creditEntry->account_id)->toBe($bankAccount->getKey());
});

it('paid: debits entity AP account via to()', function () {
    $supplier = makeSupplier(42, 'Acme Supply');
    $journal = Accounting::paid(300000, 'Payment')
        ->to($supplier)
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $resolver = app(AccountResolver::class);
    $expectedAccount = $resolver->resolveEntityAccount($supplier, AccountSystemKey::AccountsPayable);

    expect($debitEntry->account_id)->toBe($expectedAccount->getKey());
});

it('paid: uses toAccount for explicit debit', function () {
    $account = Account::query()->where('system_key', AccountSystemKey::RentExpense)->first();

    $journal = Accounting::paid(300000, 'Rent')
        ->toAccount($account)
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    expect($debitEntry->account_id)->toBe($account->getKey());
});

it('paid: throws without target', function () {
    Accounting::paid(300000, 'Payment')->commit();
})->throws(\LogicException::class, 'requires a target');

it('paid: fires JournalPaidEvent', function () {
    Event::fake([JournalPaidEvent::class]);

    $journal = Accounting::paid(300000, 'Payment')
        ->to(makeSupplier())
        ->commit();

    Event::assertDispatched(JournalPaidEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

// ── SoldTransaction ──

it('sold: creates balanced entries for credit sale', function () {
    $journal = Accounting::sold(150000, 'Product sale')
        ->to(makeCustomer())
        ->commit();

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebit())->toBe(150000);
    expect($journal->totalCredit())->toBe(150000);
    expect($journal->entries)->toHaveCount(2);
});

it('sold: debits entity AR for credit sale', function () {
    $customer = makeCustomer(42, 'Acme Corp');
    $journal = Accounting::sold(150000, 'Sale')
        ->to($customer)
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $resolver = app(AccountResolver::class);
    $expectedAccount = $resolver->resolveEntityAccount($customer, AccountSystemKey::AccountsReceivable);

    expect($debitEntry->account_id)->toBe($expectedAccount->getKey());
});

it('sold: debits cash for cash sale', function () {
    $journal = Accounting::sold(150000, 'Cash sale')
        ->forCash()
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    expect($debitEntry->account_id)->toBe($cashAccount->getKey());
});

it('sold: debits bank for bank sale', function () {
    $journal = Accounting::sold(150000, 'Bank sale')
        ->forBank()
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    expect($debitEntry->account_id)->toBe($bankAccount->getKey());
});

it('sold: credits sales revenue by default', function () {
    $journal = Accounting::sold(150000, 'Sale')
        ->forCash()
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $revenueAccount = Account::query()->where('system_key', AccountSystemKey::SalesRevenue)->first();

    expect($creditEntry->account_id)->toBe($revenueAccount->getKey());
});

it('sold: supports custom revenue account', function () {
    $journal = Accounting::sold(150000, 'Service sale')
        ->forCash()
        ->forRevenue(AccountSystemKey::ServiceRevenue)
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $revenueAccount = Account::query()->where('system_key', AccountSystemKey::ServiceRevenue)->first();

    expect($creditEntry->account_id)->toBe($revenueAccount->getKey());
});

it('sold: throws without target', function () {
    Accounting::sold(150000, 'Sale')->commit();
})->throws(\LogicException::class, 'requires a target');

it('sold: fires JournalSoldEvent', function () {
    Event::fake([JournalSoldEvent::class]);

    $journal = Accounting::sold(150000, 'Sale')
        ->forCash()
        ->commit();

    Event::assertDispatched(JournalSoldEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

// ── PurchasedTransaction ──

it('purchased: creates balanced entries', function () {
    $journal = Accounting::purchased(200000, 'Equipment')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->from(makeSupplier())
        ->commit();

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebit())->toBe(200000);
    expect($journal->totalCredit())->toBe(200000);
    expect($journal->entries)->toHaveCount(2);
});

it('purchased: debits expense account', function () {
    $journal = Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->from(makeSupplier())
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $expenseAccount = Account::query()->where('system_key', AccountSystemKey::OfficeSuppliesExpense)->first();

    expect($debitEntry->account_id)->toBe($expenseAccount->getKey());
});

it('purchased: credits entity AP via from()', function () {
    $supplier = makeSupplier(42, 'Acme Supply');
    $journal = Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->from($supplier)
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $resolver = app(AccountResolver::class);
    $expectedAccount = $resolver->resolveEntityAccount($supplier, AccountSystemKey::AccountsPayable);

    expect($creditEntry->account_id)->toBe($expectedAccount->getKey());
});

it('purchased: credits cash for cash purchase', function () {
    $journal = Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->forCash()
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $cashAccount = Account::query()->where('system_key', AccountSystemKey::CashOnHand)->first();

    expect($creditEntry->account_id)->toBe($cashAccount->getKey());
});

it('purchased: credits bank for bank purchase', function () {
    $journal = Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->forBank()
        ->commit();

    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);
    $bankAccount = Account::query()->where('system_key', AccountSystemKey::CashInBank)->first();

    expect($creditEntry->account_id)->toBe($bankAccount->getKey());
});

it('purchased: throws without payment source', function () {
    Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->commit();
})->throws(\LogicException::class, 'requires a payment source');

it('purchased: fires JournalPurchasedEvent', function () {
    Event::fake([JournalPurchasedEvent::class]);

    $journal = Accounting::purchased(200000, 'Supplies')
        ->forExpense(AccountSystemKey::OfficeSuppliesExpense)
        ->forCash()
        ->commit();

    Event::assertDispatched(JournalPurchasedEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

// ── AdjustmentTransaction ──

it('adjustment: creates balanced entries', function () {
    $debitAccount = Account::query()->where('system_key', AccountSystemKey::Inventory)->first();
    $creditAccount = Account::query()->where('system_key', AccountSystemKey::CostOfRevenue)->first();

    $journal = Accounting::adjustment(50000, 'Inventory correction')
        ->debit($debitAccount)
        ->credit($creditAccount)
        ->commit();

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebit())->toBe(50000);
    expect($journal->totalCredit())->toBe(50000);
    expect($journal->entries)->toHaveCount(2);
});

it('adjustment: uses specified debit and credit accounts', function () {
    $debitAccount = Account::query()->where('system_key', AccountSystemKey::Inventory)->first();
    $creditAccount = Account::query()->where('system_key', AccountSystemKey::CostOfRevenue)->first();

    $journal = Accounting::adjustment(50000, 'Correction')
        ->debit($debitAccount)
        ->credit($creditAccount)
        ->commit();

    $debitEntry = $journal->entries->firstWhere('debit', '>', 0);
    $creditEntry = $journal->entries->firstWhere('credit', '>', 0);

    expect($debitEntry->account_id)->toBe($debitAccount->getKey());
    expect($creditEntry->account_id)->toBe($creditAccount->getKey());
});

it('adjustment: throws without debit account', function () {
    $creditAccount = Account::query()->where('system_key', AccountSystemKey::CostOfRevenue)->first();

    Accounting::adjustment(50000, 'Correction')
        ->credit($creditAccount)
        ->commit();
})->throws(\LogicException::class, 'requires a debit account');

it('adjustment: throws without credit account', function () {
    $debitAccount = Account::query()->where('system_key', AccountSystemKey::Inventory)->first();

    Accounting::adjustment(50000, 'Correction')
        ->debit($debitAccount)
        ->commit();
})->throws(\LogicException::class, 'requires a credit account');

it('adjustment: fires JournalAdjustmentEvent', function () {
    Event::fake([JournalAdjustmentEvent::class]);

    $debitAccount = Account::query()->where('system_key', AccountSystemKey::Inventory)->first();
    $creditAccount = Account::query()->where('system_key', AccountSystemKey::CostOfRevenue)->first();

    $journal = Accounting::adjustment(50000, 'Correction')
        ->debit($debitAccount)
        ->credit($creditAccount)
        ->commit();

    Event::assertDispatched(JournalAdjustmentEvent::class, function ($event) use ($journal) {
        return $event->journal->getKey() === $journal->getKey();
    });
});

// ── Common features ──

it('supports pipeThrough on named transactions', function () {
    $pipeRan = false;

    $pipe = new class($pipeRan) implements \AloongJerr\Accounting\Contracts\AccountingPipe
    {
        public function __construct(private bool &$flag) {}

        public function handle(mixed $transaction, Closure $next): mixed
        {
            $this->flag = true;

            return $next($transaction);
        }
    };

    Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->pipeThrough([$pipe])
        ->commit();

    expect($pipeRan)->toBeTrue();
});

it('supports onDate on named transactions', function () {
    $journal = Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->onDate('2026-01-15')
        ->commit();

    expect($journal->date->toDateString())->toBe('2026-01-15');
});

it('supports comments on named transactions', function () {
    $journal = Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->comment('Invoice #123')
        ->comment('Receipt #456')
        ->commit();

    expect($journal->comments)->toBe(['Invoice #123', 'Receipt #456']);
});

it('supports forTenant on named transactions', function () {
    $transaction = Accounting::received(500000, 'Payment')
        ->from(makeCustomer())
        ->forTenant(5);

    expect($transaction->getTenantId())->toBe(5);
});
