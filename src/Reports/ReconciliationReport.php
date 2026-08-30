<?php

namespace AloongJerr\Accounting\Reports;

use AloongJerr\Accounting\Models\BankStatementLine;
use AloongJerr\Accounting\Models\JournalEntry;
use AloongJerr\Accounting\Models\Reconciliation;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\ValueObjects\ReconciliationRow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Bank Reconciliation Report.
 *
 * Compares bank statement lines against system journal entries
 * to identify matched, unmatched bank, and unmatched system items.
 *
 * Usage:
 *   Accounting::reconciliationReport($reconciliationId)->get();
 *   Accounting::reconciliationReport($reconciliationId)->summary();
 */
class ReconciliationReport extends Report
{
    protected int $reconciliationId;

    public function __construct(int $reconciliationId)
    {
        parent::__construct();
        $this->reconciliationId = $reconciliationId;
    }

    /**
     * Generate the reconciliation report data.
     *
     * @return Collection<int, ReconciliationRow>
     */
    public function get(): Collection
    {
        $reconciliation = Reconciliation::with('statementLines')->find($this->reconciliationId);

        if (! $reconciliation) {
            return new Collection();
        }

        $rows = new Collection();

        // 1. Matched items - bank lines that have been matched to system entries
        $matchedLines = $reconciliation->statementLines->where('is_matched', true);
        foreach ($matchedLines as $line) {
            $rows->push(new ReconciliationRow(
                type: 'matched',
                statementLineId: $line->id,
                journalEntryId: $line->journal_entry_id,
                date: $line->transaction_date->format('Y-m-d'),
                description: $line->description,
                reference: $line->reference ?? '',
                amount: abs($line->amount),
                bankType: $line->type,
            ));
        }

        // 2. Unmatched bank lines - bank lines without system matches
        $unmatchedBankLines = $reconciliation->statementLines->where('is_matched', false);
        foreach ($unmatchedBankLines as $line) {
            $rows->push(new ReconciliationRow(
                type: 'unmatched_bank',
                statementLineId: $line->id,
                journalEntryId: null,
                date: $line->transaction_date->format('Y-m-d'),
                description: $line->description,
                reference: $line->reference ?? '',
                amount: abs($line->amount),
                bankType: $line->type,
            ));
        }

        // 3. Unmatched system entries - system entries without bank matches
        $matchedEntryIds = $matchedLines->pluck('journal_entry_id')->filter()->unique()->all();

        $systemEntries = JournalEntry::query()
            ->where('account_id', $reconciliation->account_id)
            ->whereNotIn('id', $matchedEntryIds)
            ->journalDateBetween(
                $reconciliation->start_date->format('Y-m-d'),
                $reconciliation->end_date->format('Y-m-d')
            )
            ->notVoid()
            ->with('journal')
            ->get();

        foreach ($systemEntries as $entry) {
            // Determine the amount and type from the system entry
            $amount = $entry->debit > 0 ? $entry->debit : $entry->credit;
            $bankType = $entry->debit > 0 ? 'credit' : 'debit'; // System debit = bank credit, vice versa

            $rows->push(new ReconciliationRow(
                type: 'unmatched_system',
                statementLineId: 0,
                journalEntryId: $entry->id,
                date: $entry->journal->date->format('Y-m-d'),
                description: $entry->description ?? $entry->journal->description,
                reference: '',
                amount: $amount,
                bankType: $bankType,
            ));
        }

        // Sort by date
        return $rows->sortBy('date')->values();
    }

    /**
     * Get summary for the reconciliation report.
     *
     * @return object{
     *     reconciliation_id: int,
     *     account_id: int,
     *     bank_statement_balance: int,
     *     system_balance: int,
     *     matched_total: int,
     *     unmatched_bank_total: int,
     *     unmatched_system_total: int,
     *     difference: int,
     *     is_balanced: bool
     * }
     */
    public function summary(): object
    {
        $reconciliation = Reconciliation::find($this->reconciliationId);

        if (! $reconciliation) {
            return (object) [
                'reconciliation_id' => 0,
                'account_id' => 0,
                'bank_statement_balance' => 0,
                'system_balance' => 0,
                'matched_total' => 0,
                'unmatched_bank_total' => 0,
                'unmatched_system_total' => 0,
                'difference' => 0,
                'is_balanced' => true,
            ];
        }

        $statementLines = $reconciliation->statementLines;

        // Bank statement balance = opening + net activity
        $bankCredits = $statementLines->where('type', 'credit')->sum(fn ($l) => abs($l->amount));
        $bankDebits = $statementLines->where('type', 'debit')->sum(fn ($l) => abs($l->amount));
        $bankStatementBalance = $reconciliation->opening_balance + $bankCredits - $bankDebits;

        // Get system balance for the account at end of period
        $balanceService = app(BalanceService::class);
        $endDate = $reconciliation->end_date instanceof Carbon
            ? $reconciliation->end_date
            : Carbon::parse($reconciliation->end_date);
        $accountBalance = $balanceService->getAccountBalance($reconciliation->account_id, $endDate);
        $systemBalance = $accountBalance->balance;

        // Matched/unmatched totals
        $matchedTotal = $statementLines->where('is_matched', true)->sum(fn ($l) => abs($l->amount));
        $unmatchedBankTotal = $statementLines->where('is_matched', false)->sum(fn ($l) => abs($l->amount));

        // Unmatched system entries total
        $matchedEntryIds = $statementLines->where('is_matched', true)
            ->pluck('journal_entry_id')->filter()->unique()->all();

        $unmatchedSystemEntries = JournalEntry::query()
            ->where('account_id', $reconciliation->account_id)
            ->whereNotIn('id', $matchedEntryIds)
            ->journalDateBetween(
                $reconciliation->start_date->format('Y-m-d'),
                $reconciliation->end_date->format('Y-m-d')
            )
            ->notVoid()
            ->get();

        $unmatchedSystemTotal = $unmatchedSystemEntries->sum(fn ($e) => $e->debit > 0 ? $e->debit : $e->credit);

        // Difference = bank balance - system balance
        $difference = $bankStatementBalance - $systemBalance;

        return (object) [
            'reconciliation_id' => $reconciliation->id,
            'account_id' => $reconciliation->account_id,
            'bank_statement_balance' => $bankStatementBalance,
            'system_balance' => $systemBalance,
            'matched_total' => $matchedTotal,
            'unmatched_bank_total' => $unmatchedBankTotal,
            'unmatched_system_total' => $unmatchedSystemTotal,
            'difference' => $difference,
            'is_balanced' => $difference === 0,
        ];
    }
}
