<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Traits\HasCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    use HasCurrency;

    protected $fillable = [
        'date',
        'description',
        'reference_type',
        'reference_id',
        'status',
        'company_id',
        'currency',
        'comments',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => JournalStatus::class,
        'reference_id' => 'integer',
        'company_id' => 'integer',
        'comments' => 'array',
    ];

    // ── Relationships ──

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ──

    public function scopePosted($query)
    {
        return $query->where('status', JournalStatus::Posted);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', JournalStatus::Draft);
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDateBetween($query, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query;
    }

    // ── Helpers ──

    /**
     * Check if total debits equal total credits.
     */
    public function isBalanced(): bool
    {
        return $this->totalDebit() === $this->totalCredit();
    }

    /**
     * Get total debit amount.
     */
    public function totalDebit(): int
    {
        return (int) $this->entries()->sum('debit');
    }

    /**
     * Get total credit amount.
     */
    public function totalCredit(): int
    {
        return (int) $this->entries()->sum('credit');
    }

    /**
     * Post the journal entry (finalize it).
     */
    public function post(): bool
    {
        if (! $this->isBalanced()) {
            return false;
        }

        return $this->update(['status' => JournalStatus::Posted]);
    }

    /**
     * Void the journal entry.
     */
    public function void(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        return $this->update(['status' => JournalStatus::Void]);
    }
}
