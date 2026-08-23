<?php

namespace AloongJerr\Accounting\Traits;

use AloongJerr\Accounting\Accounting;

trait HasCurrency
{
    /**
     * Resolve the currency for the given company or fallback to config default.
     *
     * Future-ready: when company_id is present, fetch currency from companies table.
     * Currently returns the default currency from config.
     */
    protected function resolveCurrency(?int $companyId = null): string
    {
        if ($companyId) {
            $companyClass = "\App\Models\Company";
//             Future: fetch from companies table
             if (class_exists($companyClass)) {
                 $company = $companyClass::query()->find($companyId);
                 if ($company && $company->currency) {
                     return $company->currency;
                 }
             }
        }

        return Accounting::config('currency', 'USD');
    }
}
