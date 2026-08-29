<?php

namespace AloongJerr\Accounting\ValueObjects;

/**
 * Represents a single row in the Budget vs Actual report.
 *
 * Each row compares the budgeted amount against the actual spending
 * for a specific account within a period.
 *
 * @property int $accountId
 * @property string $accountName
 * @property string $accountCode
 * @property int $budgeted Budgeted amount in cents
 * @property int $actual Actual amount in cents
 * @property int $variance Difference (budgeted - actual) in cents
 * @property float|null $variancePercentage Variance as a percentage
 */
class BudgetRow
{
    public function __construct(
        public readonly int $accountId,
        public readonly string $accountName,
        public readonly string $accountCode,
        public readonly int $budgeted,
        public readonly int $actual,
        public readonly int $variance,
        public readonly ?float $variancePercentage,
    ) {}
}
