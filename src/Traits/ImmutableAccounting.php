<?php

namespace AloongJerr\Accounting\Traits;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Enums\JournalStatus;
use AloongJerr\Accounting\Exceptions\ImmutableAccountingException;

/**
 * Enforces immutability rules for accounting data in production.
 *
 * When immutable config is true (default):
 * - Blocks deletion of records in production
 * - Blocks updates to posted/void/reversed journals in production
 * - Ensures audit trail integrity for financial data
 *
 * When immutable config is false:
 * - Allows soft deletes (use SoftDeletes trait separately)
 * - Allows updates to all journals regardless of status
 * - Useful for development/testing environments
 *
 * Corrections in production must use void() or adjustment entries
 * instead of direct modifications.
 */
trait ImmutableAccounting
{
    protected static function bootImmutableAccounting(): void
    {
        $isImmutable = Accounting::config('immutable', true);

        // Block deletion when immutable in production
        static::deleting(function () use ($isImmutable) {
            if ($isImmutable && app()->environment('production')) {
                throw ImmutableAccountingException::cannotDelete(class_basename(static::class));
            }
        });

        // Block updates to posted journals when immutable in production
        static::updating(function ($model) use ($isImmutable) {
            if (! $isImmutable || ! app()->environment('production')) {
                return;
            }

            $originalStatus = $model->getOriginal('status');

            // Handle both enum and string values
            $originalStatusValue = $originalStatus instanceof JournalStatus
                ? $originalStatus->value
                : $originalStatus;

            // Check if original status is Posted, Void, or Reversed (final statuses)
            if (in_array($originalStatusValue, [
                JournalStatus::Posted->value,
                JournalStatus::Void->value,
                JournalStatus::Reversed->value,
            ])) {
                throw ImmutableAccountingException::cannotUpdatePosted();
            }
        });
    }
}
