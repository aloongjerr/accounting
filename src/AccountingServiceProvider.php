<?php

namespace AloongJerr\Accounting;

use AloongJerr\Accounting\Commands\AccountingCommand;
use AloongJerr\Accounting\Commands\GenerateSnapshotsCommand;
use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use AloongJerr\Accounting\Resolvers\AccountResolver;
use AloongJerr\Accounting\Services\AccountingService;
use AloongJerr\Accounting\Services\BalanceService;
use AloongJerr\Accounting\Snapshots\SnapshotManager;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AccountingServiceProvider extends PackageServiceProvider
{
    public static string $name = 'accounting';

    public static string $viewNamespace = 'accounting';

    public static string $packageName = 'aloongjerr/accounting';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub(static::$packageName)
                    ->endWith(function () use ($command) {
                        $command->call('db:seed', ['--class' => ChartOfAccountsSeeder::class]);
                    });
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AccountResolver::class);
        $this->app->singleton(BalanceService::class);
        $this->app->singleton(SnapshotManager::class);
        $this->app->singleton(AccountingConfiguration::class);

        $this->app->singleton(AccountingService::class, function ($app) {
            return new AccountingService(
                $app->make(AccountResolver::class),
                $app->make(BalanceService::class),
                $app->make(SnapshotManager::class),
            );
        });
    }

    public function packageBooted(): void
    {
        $this->validateRegisteredEnums();

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        if (app()->runningInConsole()) {
            // Handle Stubs
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/accounting/{$file->getFilename()}"),
                ], 'accounting-stubs');
            }

            // Register scheduled tasks only when snapshot driver is 'scheduled'
            if (Accounting::config('snapshot.driver') === 'scheduled') {
                $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                    $config = app(AccountingConfiguration::class);
                    $scheduleCallback = $config->getScheduleCallback();
                    
                    if ($scheduleCallback !== null) {
                        // User has configured custom schedule - pass $schedule directly
                        $scheduleCallback($schedule);
                    } else {
                        // Default schedule: use time from config (default 02:00)
                        $scheduleTime = Accounting::config('snapshot.schedule_time', '02:00');
                        $schedule->command('accounting:generate-snapshots')
                            ->dailyAt($scheduleTime)
                            ->description('Generate daily account balance snapshots');
                    }
                });
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return static::$packageName;
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('accounting', __DIR__ . '/../resources/dist/components/accounting.js'),
            // Css::make('accounting-styles', __DIR__ . '/../resources/dist/accounting.css'),
            // Js::make('accounting-scripts', __DIR__ . '/../resources/dist/accounting.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            AccountingCommand::class,
            GenerateSnapshotsCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_accounts_table',
            'create_journals_table',
            'create_journal_entries_table',
            'create_account_snapshots_table',
            'create_budgets_table',
            'create_reconciliation_tables',
        ];
    }

    /**
     * Validate that all registered account key enums implement HasAccountIdentity.
     */
    protected function validateRegisteredEnums(): void
    {
        $enums = Accounting::config('account_keys', []);

        foreach ($enums as $enumClass) {
            if (! is_subclass_of($enumClass, HasAccountIdentity::class)) {
                throw new \InvalidArgumentException(
                    "Account key enum [{$enumClass}] must implement [" . HasAccountIdentity::class . '].'
                );
            }
        }
    }
}
