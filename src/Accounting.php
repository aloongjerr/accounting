<?php

namespace AloongJerr\Accounting;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;

class Accounting {
    /**
     * Get a configuration value from the accounting package.
     *
     * @param 'currency'|'fiscal_year'|'fiscal_year.start_month'|'fiscal_year.end_month'|'account_keys' $key
     * @param mixed|null $default
     * @return Repository|Application|object|mixed|null
     */
    public static function config(string $key, mixed $default = null): mixed
    {
        return config("accounting.{$key}", $default);
    }
}
