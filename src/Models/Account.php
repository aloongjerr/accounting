<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use AloongJerr\Accounting\Traits\HasAccountingConnection;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property AccountType $type
 * @property AccountSystemKey|BackedEnum $system_key
 * @property int|null $parent_id
 * @property string|null $model_type
 * @property int|null $model_id
 * @property int|null $tenant_id
 * @property string $currency
 * @property string|null $description
 * @property bool $is_active
 *
 * @property Account|null $parent
 * @property Collection<Account> $children
 * @property Collection<JournalEntry> $journalEntries
 * @property Model|null $model
 *
 * @method static Builder active()
 * @method static Builder ofType(AccountType $type)
 * @method static Builder systemKey(AccountSystemKey $key)
 * @method static Builder leaf()
 */
class Account extends Model
{
    use HasAccountingConnection;

    protected $fillable = [
        'name',
        'code',
        'type',
        'system_key',
        'parent_id',
        'model_type',
        'model_id',
        'tenant_id',
        'currency',
        'description',
        'is_active',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'system_key' => AccountSystemKey::class,
        'parent_id' => 'integer',
        'model_id' => 'integer',
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
    ];

    // ── Relationships ──

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, AccountType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSystemKey(Builder $query, AccountSystemKey $key): Builder
    {
        return $query->where('system_key', $key);
    }

    public function scopeLeaf(Builder $query): Builder
    {
        return $query->where('type', AccountType::Account);
    }

    // ── Helpers ──

    /**
     * Check if this is a leaf (transactable) account.
     */
    public function isLeaf(): bool
    {
        return $this->type === AccountType::Account;
    }

    /**
     * Check if this is a group account.
     */
    public function isGroup(): bool
    {
        return $this->type === AccountType::Group;
    }

    /**
     * Check if this is a category account.
     */
    public function isCategory(): bool
    {
        return $this->type === AccountType::Category;
    }

    /**
     * Get all ancestor accounts up the hierarchy.
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return array_reverse($ancestors);
    }

    /**
     * Get all descendant accounts.
     */
    public function getDescendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get all leaf (transactable) accounts under this account.
     */
    public function getLeafAccounts(): array
    {
        if ($this->isLeaf()) {
            return [$this];
        }

        return array_values(array_filter($this->getDescendants(), fn (Account $account) => $account->isLeaf()));
    }

    /**
     * Calculate the balance for this account (debit - credit).
     * For leaf accounts, returns direct balance.
     * For groups/categories, returns sum of all leaf descendants.
     */
    public function getBalance(?string $startDate = null, ?string $endDate = null): float
    {
        $accounts = $this->getLeafAccounts();

        $query = JournalEntry::whereIn('account_id', array_map(fn ($a) => $a->getKey(), $accounts));

        if ($startDate) {
            $query->whereHas('journal', fn ($q) => $q->where('date', '>=', $startDate));
        }

        if ($endDate) {
            $query->whereHas('journal', fn ($q) => $q->where('date', '<=', $endDate));
        }

        $debit = $query->sum('debit');
        $credit = $query->sum('credit');

        return (float) ($debit - $credit);
    }
}
