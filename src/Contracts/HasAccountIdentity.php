<?php

namespace AloongJerr\Accounting\Contracts;

/**
 * Contract for account system key enums that define account identity.
 *
 * Any custom enum registered in config('accounting.account_keys')
 * must implement this interface to provide parent-child relationships
 * and account code generation.
 */
interface HasAccountIdentity
{
    /**
     * Get the parent account system key for this key.
     * Returns null for top-level group keys.
     */
    public function parentKey(): ?self;

    /**
     * Generate a unique account code for this key.
     */
    public function getCode(): string;
}
