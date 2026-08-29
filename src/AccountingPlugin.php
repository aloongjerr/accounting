<?php

namespace AloongJerr\Accounting;

use AloongJerr\Accounting\Filament\Pages\BalanceSheet;
use AloongJerr\Accounting\Filament\Pages\IncomeStatement;
use AloongJerr\Accounting\Filament\Pages\TrialBalance;
use AloongJerr\Accounting\Filament\Resources\AccountResource;
use AloongJerr\Accounting\Filament\Resources\JournalResource;
use AloongJerr\Accounting\Filament\Widgets\AccountBalanceWidget;
use AloongJerr\Accounting\Filament\Widgets\FinancialSummaryWidget;
use AloongJerr\Accounting\Filament\Widgets\RecentJournalsWidget;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Filament plugin for the accounting package.
 *
 * Registers resources, pages, and widgets with the Filament panel.
 * Provides runtime configuration for UI features.
 */
class AccountingPlugin implements Plugin
{
    protected Closure|string|null $resourceNavigationGroup = null;

    protected bool $createTransaction = false;

    protected bool $manualTenantMode = false;

    public function getId(): string
    {
        return 'accounting';
    }

    /**
     * Override the default navigation group for accounting resources.
     */
    public function resourceNavigationGroup(Closure|string|null $group): static
    {
        $this->resourceNavigationGroup = $group;

        return $this;
    }

    /**
     * Enable the 'Create Transaction' action for named transactions
     * (received, paid, sold, purchased). Disabled by default.
     */
    public function canCreateTransaction(bool $condition = true): static
    {
        $this->createTransaction = $condition;

        return $this;
    }

    /**
     * Enable manual tenant mode - adds tenant selector in forms
     * for super admin/support users who manage multiple tenants.
     */
    public function manualTenantMode(bool $condition = true): static
    {
        $this->manualTenantMode = $condition;

        return $this;
    }

    /**
     * Get the navigation group for accounting resources.
     */
    public function getResourceNavigationGroup(): Closure|string|null
    {
        return $this->resourceNavigationGroup;
    }

    /**
     * Check if transaction creation is enabled.
     */
    public function isCreateTransactionEnabled(): bool
    {
        return $this->createTransaction;
    }

    /**
     * Check if manual tenant mode is enabled.
     */
    public function isManualTenantMode(): bool
    {
        return $this->manualTenantMode;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AccountResource::class,
            JournalResource::class,
        ]);

        $panel->pages([
            TrialBalance::class,
            IncomeStatement::class,
            BalanceSheet::class,
        ]);

        $panel->widgets([
            AccountBalanceWidget::class,
            RecentJournalsWidget::class,
            FinancialSummaryWidget::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
