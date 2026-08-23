<?php

namespace AloongJerr\Accounting\Contracts;

use AloongJerr\Accounting\Enums\AccountSystemKey;

interface Accountable
{
    /**
     * Get the account system key(s) this model can represent.
     *
     * @return AccountSystemKey|array<AccountSystemKey>
     */
    public function getAccountKeys(): AccountSystemKey|array;

    /**
     * Get the unique identifier for the individual account record.
     *
     * @return array{id: int|string, name: string}
     */
    public function getAccountIdentifier(): array;
}
