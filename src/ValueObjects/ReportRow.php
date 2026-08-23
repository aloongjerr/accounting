<?php

namespace AloongJerr\Accounting\ValueObjects;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use AloongJerr\Accounting\Enums\AccountType;

/**
 * Represents a single row in a financial report.
 *
 * Combines account details with balance information.
 * Used by Report::enrichWithAccountDetails() and Report::rollupBalances()
 * to return typed objects instead of raw stdClass.
 *
 * @property int $accountId
 * @property string $accountName
 * @property string|null $accountCode
 * @property AccountType $accountType
 * @property AccountSystemKey|string|null $systemKey
 * @property int|null $parentId
 * @property int $debit
 * @property int $credit
 * @property int $balance
 */
class ReportRow
{
    public function __construct(
        public readonly int $accountId,
        public readonly string $accountName,
        public readonly ?string $accountCode,
        public readonly AccountType $accountType,
        public readonly AccountSystemKey|string|null $systemKey,
        public readonly ?int $parentId,
        public int $debit,
        public int $credit,
        public int $balance,
    ) {}

    /**
     * Create from an Account model and balance values.
     */
    public static function fromAccount(
        \AloongJerr\Accounting\Models\Account $account,
        int $debit = 0,
        int $credit = 0,
        int $balance = 0,
    ): self {
        return new self(
            accountId: $account->getKey(),
            accountName: $account->name,
            accountCode: $account->code,
            accountType: $account->type,
            systemKey: $account->system_key,
            parentId: $account->parent_id,
            debit: $debit,
            credit: $credit,
            balance: $balance,
        );
    }

    /**
     * Add amounts to this row's balances (used during rollup).
     */
    public function add(int $debit, int $credit, int $balance): void
    {
        $this->debit += $debit;
        $this->credit += $credit;
        $this->balance += $balance;
    }
}
