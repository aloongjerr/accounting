<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Traits\HasAccountingConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reconciliation_id
 * @property Carbon $transaction_date
 * @property string $description
 * @property int $amount Signed: positive = credit, negative = debit
 * @property string|null $reference
 * @property string $type debit|credit (from bank perspective)
 * @property int|null $journal_entry_id Matched system entry
 * @property bool $is_matched
 * @property int|null $tenant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Reconciliation $reconciliation
 * @property JournalEntry|null $journalEntry
 *
 * @method static Builder forReconciliation(int $reconciliationId)
 * @method static Builder matched()
 * @method static Builder unmatched()
 * @method static Builder debits()
 * @method static Builder credits()
 */
class BankStatementLine extends Model
{
    use HasAccountingConnection;

    protected $fillable = [
        'reconciliation_id',
        'transaction_date',
        'description',
        'amount',
        'reference',
        'type',
        'journal_entry_id',
        'is_matched',
        'tenant_id',
    ];

    protected $casts = [
        'reconciliation_id' => 'integer',
        'transaction_date' => 'date',
        'amount' => 'integer',
        'journal_entry_id' => 'integer',
        'is_matched' => 'boolean',
        'tenant_id' => 'integer',
    ];

    protected $attributes = [
        'is_matched' => false,
        'amount' => 0,
    ];

    // ── Relationships ──

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    // ── Scopes ──

    public function scopeForReconciliation(Builder $query, int $reconciliationId): Builder
    {
        return $query->where('reconciliation_id', $reconciliationId);
    }

    public function scopeMatched(Builder $query): Builder
    {
        return $query->where('is_matched', true);
    }

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('is_matched', false);
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }

    // ── Helpers ──

    /**
     * Match this bank statement line to a journal entry.
     */
    public function matchTo(int $journalEntryId): self
    {
        $this->update([
            'journal_entry_id' => $journalEntryId,
            'is_matched' => true,
        ]);

        return $this;
    }

    /**
     * Unmatch this bank statement line.
     */
    public function unmatch(): self
    {
        $this->update([
            'journal_entry_id' => null,
            'is_matched' => false,
        ]);

        return $this;
    }

    /**
     * Get the absolute amount.
     */
    public function absoluteAmount(): int
    {
        return abs($this->amount);
    }
}
