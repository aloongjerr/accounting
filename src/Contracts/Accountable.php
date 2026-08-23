<?php

namespace AloongJerr\Accounting\Contracts;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use BackedEnum;

interface Accountable
{
    /**
     * Get the account system key(s) this model can represent.
     *
     * @return AccountSystemKey|BackedEnum|array<AccountSystemKey|BackedEnum>
     */
    public function getAccountKeys(): BackedEnum|array|AccountSystemKey;

    /**
     * Get the unique identifier for the individual account record.
     *
     * @return array{id: int|string, name: string}
     */
    public function getAccountIdentifier(): array;
}
