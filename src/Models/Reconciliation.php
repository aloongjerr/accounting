<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Traits\HasAccountingConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $opening_balance Bank opening balance in cents
 * @property int $closing_balance Bank closing balance in cents
 * @property string $status draft|completed
 * @property Carbon|null $completed_at
 * @property int|null $tenant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Account $account
 * @property BankStatementLine[] $statementLines
 *
 * @method static Builder forAccount(int $accountId)
 * @method static Builder forPeriod(string|Carbon $from, string|Carbon $to)
 * @method static Builder forTenant(?int $tenantId)
 * @method static Builder draft()
 * @method static Builder completed()
 */
class Reconciliation extends Model
{
    use HasAccountingConnection;

    protected $fillable = [
        'account_id',
        'start_date',
        'end_date',
        'opening_balance',
        'closing_balance',
        'status',
        'completed_at',
        'tenant_id',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'opening_balance' => 'integer',
        'closing_balance' => 'integer',
        'status' => 'string',
        'completed_at' => 'datetime',
        'tenant_id' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'opening_balance' => 0,
        'closing_balance' => 0,
    ];

    // ── Relationships ──

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    // ── Scopes ──

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForPeriod(Builder $query, string|Carbon $from, string|Carbon $to): Builder
    {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to = $to instanceof Carbon ? $to : Carbon::parse($to);

        return $query->where('start_date', '>=', $from)
            ->where('end_date', '<=', $to);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    // ── Helpers ──

    /**
     * Mark this reconciliation as completed.
     */
    public function complete(): self
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Check if this reconciliation overlaps with the given date range.
     */
    public function overlaps(\Carbon\Carbon $from, \Carbon\Carbon $to): bool
    {
        return $this->start_date->lte($to) && $this->end_date->gte($from);
    }

    /**
     * Get count of matched statement lines.
     */
    public function matchedCount(): int
    {
        return $this->statementLines()->where('is_matched', true)->count();
    }

    /**
     * Get count of unmatched statement lines.
     */
    public function unmatchedCount(): int
    {
        return $this->statementLines()->where('is_matched', false)->count();
    }
}
