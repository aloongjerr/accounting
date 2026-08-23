<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Traits\HasAccountingConnection;
use AloongJerr\Accounting\Traits\ImmutableAccounting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property Carbon $snapshot_date
 * @property int|null $tenant_id
 * @property string $snapshot_type
 * @property array $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static Builder forDate(Carbon $date)
 * @method static Builder forTenant(?int $tenantId)
 * @method static Builder upToDate(Carbon $date)
 * @method static Builder ofType(string $type)
 */
class AccountSnapshot extends Model
{
    use HasAccountingConnection;
    use ImmutableAccounting; // Handles both deletion and update protection
    use SoftDeletes; // Enable soft deletes when immutable=false

    protected $fillable = [
        'snapshot_date',
        'tenant_id',
        'snapshot_type',
        'data',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'tenant_id' => 'integer',
        'data' => 'array',
    ];

    // ── Scopes ──

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('snapshot_date', $date);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId !== null) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query->whereNull('tenant_id');
    }

    public function scopeUpToDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('snapshot_date', '<=', $date);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('snapshot_type', $type);
    }
}
