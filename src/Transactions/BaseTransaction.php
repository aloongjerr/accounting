<?php

namespace AloongJerr\Accounting\Transactions;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Contracts\AccountingPipe;
use AloongJerr\Accounting\Models\Journal;
use AloongJerr\Accounting\Models\JournalEntry;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

/**
 * Abstract base for all transaction builders.
 *
 * Provides the fluent API for building journal entries.
 * Each transaction type (Received, Paid, Sold, etc.) extends this
 * and implements resolveEntries() to define debit/credit mappings.
 */
abstract class BaseTransaction
{
    protected int $amount;

    protected string $description = '';

    protected ?Carbon $date = null;

    /** @var array<string> */
    protected array $comments = [];

    protected ?string $referenceType = null;

    protected ?int $referenceId = null;

    protected ?int $tenantId = null;

    /** @var array<class-string<AccountingPipe>> */
    protected array $pipes = [];

    protected AccountResolver $resolver;

    public function __construct(int $amount, string $description = '', ?AccountResolver $resolver = null)
    {
        $this->amount = $amount;
        $this->description = $description;
        $this->date = Carbon::today();
        $this->resolver = $resolver ?? App(AccountResolver::class);
    }

    /**
     * Set the transaction date.
     */
    public function onDate(string|Carbon $date): static
    {
        $this->date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $this;
    }

    /**
     * Add a comment to the journal.
     */
    public function comment(string $comment): static
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Set multiple comments at once.
     *
     * @param  array<string>  $comments
     */
    public function comments(array $comments): static
    {
        $this->comments = array_merge($this->comments, $comments);

        return $this;
    }

    /**
     * Attach a polymorphic reference to the journal.
     */
    public function reference(Model $model): static
    {
        $this->referenceType = get_class($model);
        $this->referenceId = $model->getKey();

        return $this;
    }

    /**
     * Set the tenant ID for multi-tenant support.
     */
    public function forTenant(?int $tenantId): static
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    /**
     * Add pipes to run before commit.
     *
     * @param  array<class-string<AccountingPipe>>  $pipes
     */
    public function pipeThrough(array $pipes): static
    {
        $this->pipes = array_merge($this->pipes, $pipes);

        return $this;
    }

    /**
     * Get the event class to fire after commit.
     *
     * @return class-string
     */
    abstract public function getEventClass(): string;

    /**
     * Resolve the journal entries (debit/credit) for this transaction.
     *
     * @return array<int, array{account_id: int, debit: float, credit: float, description?: string}>
     */
    abstract public function resolveEntries(): array;

    /**
     * Commit the transaction — creates journal, entries, and fires event.
     */
    public function commit(): Journal
    {
        // Run through pipes first
        $result = app(Pipeline::class)
            ->send($this)
            ->through($this->pipes)
            ->then(function (BaseTransaction $transaction) {
                return $transaction->execute();
            });

        return $result;
    }

    /**
     * Execute the commit — called after pipes pass through.
     */
    protected function execute(): Journal
    {
        return DB::transaction(function () {
            $entries = $this->resolveEntries();

            // Create journal
            $journal = Journal::query()->create([
                'date' => $this->date,
                'description' => $this->description,
                'reference_type' => $this->referenceType,
                'reference_id' => $this->referenceId,
                'status' => \AloongJerr\Accounting\Enums\JournalStatus::Draft,
                'tenant_id' => $this->tenantId,
                'currency' => $this->resolveCurrency(),
                'comments' => ! empty($this->comments) ? $this->comments : null,
            ]);

            // Create journal entries
            foreach ($entries as $entry) {
                JournalEntry::query()->create([
                    'journal_id' => $journal->getKey(),
                    'account_id' => $entry['account_id'],
                    'debit' => $entry['debit'],
                    'credit' => $entry['credit'],
                    'description' => $entry['description'] ?? null,
                ]);
            }

            // Fire event
            $eventClass = $this->getEventClass();
            event(new $eventClass($journal, $this));

            return $journal;
        });
    }

    /**
     * Resolve currency for this transaction.
     */
    protected function resolveCurrency(): string
    {
        if ($this->tenantId) {
            // Future: fetch from tenants table
        }

        return Accounting::config('currency', 'USD');
    }

    /**
     * Get the transaction amount.
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Get the transaction description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the transaction date.
     */
    public function getDate(): Carbon
    {
        return $this->date;
    }

    /**
     * Get the comments array.
     *
     * @return array<string>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    /**
     * Get the tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Get the account resolver.
     */
    public function getResolver(): AccountResolver
    {
        return $this->resolver;
    }
}
