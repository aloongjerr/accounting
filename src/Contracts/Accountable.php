<?php

namespace AloongJerr\Accounting\Contracts;

use AloongJerr\Accounting\Enums\AccountSystemKey;
use BackedEnum;

interface Accountable
{
    /**
     * Get the account system key(s) this model can represent.
     *
     * @param  array<string, mixed>  $data  Additional context data from the transaction
     * @return AccountSystemKey|BackedEnum|array<AccountSystemKey|BackedEnum>
     */
    public function getAccountKeys(array $data = []): BackedEnum|array|AccountSystemKey;

    /**
     * Get the unique identifier for the individual account record.
     *
     * @param  array<string, mixed>  $data  Additional context data from the transaction
     * @return array{id: int|string, name: string}
     */
    public function getAccountIdentifier(array $data = []): array;
}
