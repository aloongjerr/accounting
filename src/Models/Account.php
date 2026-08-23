<?php

namespace AloongJerr\Accounting\Models;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Account extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'system_key',
        'parent_id',
        'model_type',
        'model_id',
        'company_id',
        'currency',
        'description',
        'is_active',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'system_key' => AccountSystemKey::class,
        'parent_id' => 'integer',
        'model_id' => 'integer',
        'company_id' => 'integer',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, AccountType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSystemKey($query, AccountSystemKey $key)
    {
        return $query->where('system_key', $key);
    }

    public function scopeLeaf($query)
    {
        return $query->where('type', AccountType::Account);
    }

    // ── Helpers ──

    public function isLeaf(): bool
    {
        return $this->type === AccountType::Account;
    }

    public function isGroup(): bool
    {
        return $this->type === AccountType::Group;
    }

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
