<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Traits\HasAccountingConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $amount Budgeted amount in cents
 * @property string|null $description
 * @property int|null $tenant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Account $account
 *
 * @method static Builder forAccount(int $accountId)
 * @method static Builder forPeriod(string|Carbon $from, string|Carbon $to)
 * @method static Builder forTenant(?int $tenantId)
 */
class Budget extends Model
{
    use HasAccountingConnection;

    protected $fillable = [
        'account_id',
        'start_date',
        'end_date',
        'amount',
        'description',
        'tenant_id',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'integer',
        'tenant_id' => 'integer',
    ];

    // ── Relationships ──

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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

    // ── Helpers ──

    /**
     * Check if this budget overlaps with the given date range.
     */
    public function overlaps(\Carbon\Carbon $from, \Carbon\Carbon $to): bool
    {
        return $this->start_date->lte($to) && $this->end_date->gte($from);
    }
}
