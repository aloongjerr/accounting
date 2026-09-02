<?php

namespace AloongJerr\Accounting;

use Closure;

class Accounting {
    /**
     * Get a configuration value from the accounting package.
     *
     * @param 'connection'|'currency'|'fiscal_year'|'fiscal_year.start_month'|'fiscal_year.start_day'|'fiscal_year.end_month'|'fiscal_year.end_day'|'account_keys'|'snapshot'|'snapshot.driver'|'snapshot.schedule_time' $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function config(string $key, mixed $default = null): mixed
    {
        return config("accounting.{$key}", $default);
    }

    /**
     * Configure the accounting package using a fluent interface.
     *
     * @param Closure(AccountingConfiguration): void $callback
     */
    public static function configure(Closure $callback): void
    {
        $config = app(AccountingConfiguration::class);
        $callback($config);
        $config->apply();
    }

    /**
     * Set the current tenant for multi-tenancy.
     */
    public static function setTenant(?int $tenantId): void
    {
        app(AccountingConfiguration::class)->tenant($tenantId);
    }

    /**
     * Get the current tenant ID.
     */
    public static function getTenant(): ?int
    {
        return app(AccountingConfiguration::class)->getTenant();
    }
}
