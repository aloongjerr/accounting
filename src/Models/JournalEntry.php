<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Traits\HasAccountingConnection;
use AloongJerr\Accounting\Traits\ImmutableAccounting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $journal_id
 * @property int $account_id
 * @property int $debit
 * @property int $credit
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property Journal $journal
 * @property Account $account
 *
 * @method static Builder notVoid()
 * @method static Builder forTenant(?int $tenantId)
 * @method static Builder journalDateBetween(?string $from = null, ?string $to = null)
 */
class JournalEntry extends Model
{
    use HasAccountingConnection;
    use ImmutableAccounting; // Handles both deletion and update protection
    use SoftDeletes; // Enable soft deletes when immutable=false

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'journal_id' => 'integer',
        'account_id' => 'integer',
        'debit' => 'integer',
        'credit' => 'integer',
    ];

    // ── Relationships ──

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // ── Scopes ──

    /**
     * Filter entries whose journal is not void.
     */
    public function scopeNotVoid(Builder $query): Builder
    {
        return $query->whereHas('journal', fn (Builder $q) => $q->where('status', '!=', JournalStatus::Void->value));
    }

    /**
     * Filter entries by journal tenant ID.
     */
    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId !== null) {
            return $query->whereHas('journal', fn (Builder $q) => $q->where('tenant_id', $tenantId));
        }

        return $query->whereHas('journal', fn (Builder $q) => $q->whereNull('tenant_id'));
    }

    /**
     * Filter entries by journal date range.
     */
    public function scopeJournalDateBetween(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        if ($from) {
            $query->whereHas('journal', fn (Builder $q) => $q->where('date', '>=', $from));
        }

        if ($to) {
            $query->whereHas('journal', fn (Builder $q) => $q->where('date', '<=', $to));
        }

        return $query;
    }

    // ── Helpers ──

    /**
     * Get the net amount (debit - credit).
     */
    public function netAmount(): int
    {
        return $this->debit - $this->credit;
    }

    /**
     * Check if this entry is a debit entry.
     */
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Check if this entry is a credit entry.
     */
    public function isCredit(): bool
    {
        return $this->credit > 0;
    }
}
