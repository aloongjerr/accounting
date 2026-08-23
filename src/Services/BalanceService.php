<?php

namespace AloongJerr\Accounting\Services;

use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\ValueObjects\AccountActivity;
use AloongJerr\Accounting\ValueObjects\AccountBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Core balance calculation service.
 *
 * Provides efficient queries for calculating account balances
 * from journal entries. Used by reports and ledger.
 */
class BalanceService
{
    /**
     * Get cumulative balances for all leaf accounts as of a date.
     *
     * Returns AccountBalance value objects for every leaf account
     * that has had entries up to $asOf.
     *
     * @return Collection<int, AccountBalance>
     */
    public function getCumulativeBalances(Carbon $asOf, ?int $tenantId = null): Collection
    {
        $query = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('journals.date', '<=', $asOf->copy()->endOfDay())
            ->where('journals.status', '!=', JournalStatus::Void->value)
            ->where('accounts.type', AccountType::Account->value);

        if ($tenantId !== null) {
            $query->where('journals.tenant_id', $tenantId);
        } else {
            $query->whereNull('journals.tenant_id');
        }

        $rows = $query
            ->groupBy('journal_entries.account_id')
            ->select(
                'journal_entries.account_id',
                DB::raw('SUM(journal_entries.debit) as debit'),
                DB::raw('SUM(journal_entries.credit) as credit'),
                DB::raw('SUM(journal_entries.debit) - SUM(journal_entries.credit) as balance')
            )
            ->get();

        return AccountBalance::collectFromRows($rows);
    }

    /**
     * Get period activity (debits and credits) for all leaf accounts within a date range.
     *
     * @return Collection<int, AccountActivity>
     */
    public function getPeriodActivity(Carbon $from, Carbon $to, ?int $tenantId = null): Collection
    {
        $query = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('journals.date', '>=', $from->startOfDay())
            ->where('journals.date', '<=', $to->copy()->endOfDay())
            ->where('journals.status', '!=', JournalStatus::Void->value)
            ->where('accounts.type', AccountType::Account->value);

        if ($tenantId !== null) {
            $query->where('journals.tenant_id', $tenantId);
        } else {
            $query->whereNull('journals.tenant_id');
        }

        $rows = $query
            ->groupBy('journal_entries.account_id')
            ->select(
                'journal_entries.account_id',
                DB::raw('SUM(journal_entries.debit) as debit'),
                DB::raw('SUM(journal_entries.credit) as credit'),
                DB::raw('SUM(journal_entries.debit) - SUM(journal_entries.credit) as balance')
            )
            ->get();

        return AccountActivity::collectFromRows($rows);
    }

    /**
     * Get cumulative balance for a single account as of a date.
     */
    public function getAccountBalance(int $accountId, Carbon $asOf): AccountBalance
    {
        $result = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->where('journal_entries.account_id', $accountId)
            ->where('journals.date', '<=', $asOf->copy()->endOfDay())
            ->where('journals.status', '!=', JournalStatus::Void->value)
            ->selectRaw('COALESCE(SUM(journal_entries.debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(journal_entries.credit), 0) as credit')
            ->first();

        return AccountBalance::make(
            $accountId,
            (int) ($result->debit ?? 0),
            (int) ($result->credit ?? 0),
        );
    }

    /**
     * Get period activity for a single account within a date range.
     */
    public function getAccountPeriodActivity(int $accountId, Carbon $from, Carbon $to): AccountActivity
    {
        $result = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->where('journal_entries.account_id', $accountId)
            ->where('journals.date', '>=', $from->startOfDay())
            ->where('journals.date', '<=', $to->copy()->endOfDay())
            ->where('journals.status', '!=', JournalStatus::Void->value)
            ->selectRaw('COALESCE(SUM(journal_entries.debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(journal_entries.credit), 0) as credit')
            ->first();

        return AccountActivity::make(
            $accountId,
            (int) ($result->debit ?? 0),
            (int) ($result->credit ?? 0),
        );
    }
}
