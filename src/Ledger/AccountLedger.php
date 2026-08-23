<?php

namespace AloongJerr\Accounting\Ledger;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Account;
use AloongJerr\Accounting\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ledger for a single account.
 *
 * Shows all journal entries affecting an account within a period,
 * with a running balance.
 *
 * Usage:
 *   $ledger = new AccountLedger($account);
 *   $ledger->forPeriod($from, $to)->getEntries();
 */
class AccountLedger
{
    protected ?Carbon $from = null;

    protected ?Carbon $to = null;

    protected ?int $tenantId = null;

    /** @var Collection<int, LedgerEntry>|null */
    protected ?Collection $entries = null;

    public function __construct(
        protected Account $account,
    ) {}

    /**
     * Set the period for the ledger.
     */
    public function forPeriod(Carbon $from, Carbon $to): static
    {
        $this->from = $from;
        $this->to = $to;

        return $this;
    }

    /**
     * Set the tenant filter.
     */
    public function forTenant(?int $tenantId): static
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    /**
     * Get the opening balance (cumulative before the period start).
     */
    public function getOpeningBalance(): int
    {
        if (! $this->from) {
            return 0;
        }

        $query = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->where('journal_entries.account_id', $this->account->getKey())
            ->where('journals.date', '<', $this->from->startOfDay())
            ->where('journals.status', '!=', JournalStatus::Void->value);

        if ($this->tenantId !== null) {
            $query->where('journals.tenant_id', $this->tenantId);
        } else {
            $query->whereNull('journals.tenant_id');
        }

        $result = $query->selectRaw('COALESCE(SUM(journal_entries.debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(journal_entries.credit), 0) as credit')
            ->first();

        return (int) (($result->debit ?? 0) - ($result->credit ?? 0));
    }

    /**
     * Get the closing balance (cumulative through the period end).
     */
    public function getClosingBalance(): int
    {
        if (! $this->to) {
            return $this->getOpeningBalance();
        }

        $query = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->where('journal_entries.account_id', $this->account->getKey())
            ->where('journals.date', '<=', $this->to->copy()->endOfDay())
            ->where('journals.status', '!=', JournalStatus::Void->value);

        if ($this->tenantId !== null) {
            $query->where('journals.tenant_id', $this->tenantId);
        } else {
            $query->whereNull('journals.tenant_id');
        }

        $result = $query->selectRaw('COALESCE(SUM(journal_entries.debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(journal_entries.credit), 0) as credit')
            ->first();

        return (int) (($result->debit ?? 0) - ($result->credit ?? 0));
    }

    /**
     * Get total debits for the period.
     */
    public function getTotalDebit(): int
    {
        return $this->getEntries()->sum(fn (LedgerEntry $e) => $e->debit);
    }

    /**
     * Get total credits for the period.
     */
    public function getTotalCredit(): int
    {
        return $this->getEntries()->sum(fn (LedgerEntry $e) => $e->credit);
    }

    /**
     * Get all ledger entries for the period.
     *
     * Uses Eloquent with the JournalEntry model and its journal relationship.
     *
     * @return Collection<int, LedgerEntry>
     */
    public function getEntries(): Collection
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $query = $this->account->journalEntries()
            ->notVoid()
            ->forTenant($this->tenantId)
            ->with('journal')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->orderBy('journals.date')
            ->orderBy('journal_entries.id')
            ->select('journal_entries.*');

        if ($this->from) {
            $query->where('journals.date', '>=', $this->from->startOfDay());
        }

        if ($this->to) {
            $query->where('journals.date', '<=', $this->to->copy()->endOfDay());
        }

        $rows = $query->get();

        $runningBalance = $this->getOpeningBalance();
        $entries = [];

        foreach ($rows as $row) {
            $runningBalance += ($row->debit - $row->credit);
            $entries[] = LedgerEntry::fromRow($row, $runningBalance);
        }

        $this->entries = new Collection($entries);

        return $this->entries;
    }

    /**
     * Get the account for this ledger.
     */
    public function getAccount(): Account
    {
        return $this->account;
    }
}
