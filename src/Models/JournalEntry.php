<?php

namespace AloongJerr\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $journal_id
 * @property int $account_id
 * @property int $debit
 * @property int $credit
 * @property string|null $description
 *
 * @property Journal $journal
 * @property Account $account
 */
class JournalEntry extends Model
{
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
