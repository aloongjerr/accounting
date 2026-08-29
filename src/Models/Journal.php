<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Traits\HasAccountingConnection;
use AloongJerr\Accounting\Traits\HasCurrency;
use AloongJerr\Accounting\Traits\ImmutableAccounting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property string $description
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property JournalStatus $status
 * @property int|null $tenant_id
 * @property string|null $currency
 * @property array|null $comments
 * @property string|null $void_remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property Collection<JournalEntry> $entries
 * @property Model|null $reference
 *
 * @method static Builder posted()
 * @method static Builder draft()
 * @method static Builder forTenant(?int $tenantId)
 * @method static Builder dateBetween(?string $startDate, ?string $endDate)
 */
class Journal extends Model
{
    use HasAccountingConnection;
    use HasCurrency;
    use ImmutableAccounting; // Handles both deletion and update protection
    use SoftDeletes; // Enable soft deletes when immutable=false

    protected $fillable = [
        'date',
        'description',
        'reference_type',
        'reference_id',
        'status',
        'tenant_id',
        'currency',
        'comments',
        'void_remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => JournalStatus::class,
        'reference_id' => 'integer',
        'tenant_id' => 'integer',
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

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', JournalStatus::Posted);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', JournalStatus::Draft);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeDateBetween(Builder $query, ?string $startDate, ?string $endDate): Builder
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
    public function void(?string $remarks = null): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        return $this->update([
            'status' => JournalStatus::Void,
            'void_remarks' => $remarks,
        ]);
    }
}
