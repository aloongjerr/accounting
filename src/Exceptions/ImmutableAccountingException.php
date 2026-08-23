<?php

namespace AloongJerr\Accounting\Exceptions;

use LogicException;

/**
 * Exception thrown when attempting to modify immutable accounting data.
 *
 * This exception is raised in production when:
 * - Attempting to delete accounting records (journals, entries, snapshots)
 * - Attempting to update posted/void/reversed journals
 *
 * Accounting data must remain immutable to preserve audit trail integrity.
 * Use void() method or create adjustment entries instead of direct modifications.
 */
class ImmutableAccountingException extends LogicException
{
    /**
     * Create exception for deletion attempt.
     */
    public static function cannotDelete(string $model): self
    {
        return new self(
            __('accounting::exceptions.cannot_delete', ['model' => $model])
        );
    }

    /**
     * Create exception for update attempt on posted journal.
     */
    public static function cannotUpdatePosted(): self
    {
        return new self(
            __('accounting::exceptions.cannot_update_posted')
        );
    }
}
