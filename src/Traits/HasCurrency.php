<?php

namespace AloongJerr\Accounting\Traits;

use AloongJerr\Accounting\Accounting;

trait HasCurrency
{
    /**
     * Resolve the currency for the given tenant or fallback to config default.
     *
     * Future-ready: when tenant_id is present, fetch currency from tenants table.
     * Currently returns the default currency from config.
     */
    protected function resolveCurrency(?int $tenantId = null): string
    {
        if ($tenantId) {
            $tenantClass = "\App\Models\Tenant";
//             Future: fetch from tenants table
             if (class_exists($tenantClass)) {
                 $tenant = $tenantClass::query()->find($tenantId);
                 if ($tenant && $tenant->currency) {
                     return $tenant->currency;
                 }
             }
        }

        return Accounting::config('currency', 'USD');
    }
}
