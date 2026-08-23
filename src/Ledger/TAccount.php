<?php

namespace AloongJerr\Accounting\Ledger;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * T-Account view for a single account.
 *
 * Provides a traditional T-account layout with debits on the left,
 * credits on the right, and running balance.
 *
 * Usage:
 *   $tAccount = new TAccount($account);
 *   $tAccount->forPeriod($from, $to)->get();
 */
class TAccount
{
    protected ?Carbon $from = null;

    protected ?Carbon $to = null;

    protected ?int $tenantId = null;

    protected ?int $openingBalance = null;

    protected ?int $closingBalance = null;

    /** @var Collection<int, LedgerEntry>|null */
    protected ?Collection $debitEntries = null;

    /** @var Collection<int, LedgerEntry>|null */
    protected ?Collection $creditEntries = null;

    public function __construct(
        protected Account $account,
    ) {}

    /**
     * Set the period for the T-account.
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
        if ($this->openingBalance !== null) {
            return $this->openingBalance;
        }

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

        $this->openingBalance = (int) (($result->debit ?? 0) - ($result->credit ?? 0));

        return $this->openingBalance;
    }

    /**
     * Get the closing balance (cumulative through the period end).
     */
    public function getClosingBalance(): int
    {
        if ($this->closingBalance !== null) {
            return $this->closingBalance;
        }

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

        $this->closingBalance = (int) (($result->debit ?? 0) - ($result->credit ?? 0));

        return $this->closingBalance;
    }

    /**
     * Get total debits for the period.
     */
    public function getTotalDebit(): int
    {
        return $this->getDebitEntries()->sum(fn (LedgerEntry $e) => $e->debit);
    }

    /**
     * Get total credits for the period.
     */
    public function getTotalCredit(): int
    {
        return $this->getCreditEntries()->sum(fn (LedgerEntry $e) => $e->credit);
    }

    /**
     * Get debit entries (left side of T).
     *
     * @return Collection<int, LedgerEntry>
     */
    public function getDebitEntries(): Collection
    {
        if ($this->debitEntries !== null) {
            return $this->debitEntries;
        }

        $this->loadEntries();

        return $this->debitEntries;
    }

    /**
     * Get credit entries (right side of T).
     *
     * @return Collection<int, LedgerEntry>
     */
    public function getCreditEntries(): Collection
    {
        if ($this->creditEntries !== null) {
            return $this->creditEntries;
        }

        $this->loadEntries();

        return $this->creditEntries;
    }

    /**
     * Get the account for this T-account.
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * Load and separate entries into debit and credit sides.
     *
     * Uses Eloquent with the JournalEntry model and its journal relationship.
     */
    protected function loadEntries(): void
    {
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

        $debits = [];
        $credits = [];

        foreach ($rows as $row) {
            if ($row->debit > 0) {
                $debits[] = LedgerEntry::fromRow($row);
            }
            if ($row->credit > 0) {
                $credits[] = LedgerEntry::fromRow($row);
            }
        }

        $this->debitEntries = new Collection($debits);
        $this->creditEntries = new Collection($credits);
    }
}
