<?php

namespace AloongJerr\Accounting\Commands;

use AloongJerr\Accounting\Database\Seeders\ChartOfAccountsSeeder;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class AccountingInstallCommand extends InstallCommand
{
    public function __construct(\Spatie\LaravelPackageTools\Package $package)
    {
        // Append --connection option before parent constructor parses the signature
        $this->signature .= ' {--connection= : The database connection for accounting tables}';

        parent::__construct($package);
    }

    public function handle()
    {
        $connection = $this->option('connection');

        if (! $connection) {
            $default = config('database.default');
            $connection = $this->ask(
                "Which database connection should accounting use?",
                $default
            );
        }

        // Use null (default connection) when the user picks the app's default
        $connectionValue = ($connection === config('database.default')) ? '' : $connection;

        $this->updateEnvConnection($connectionValue);

        // Also set for the current process so config picks it up immediately
        if ($connectionValue) {
            putenv("ACCOUNTING_CONNECTION={$connectionValue}");
            $_ENV['ACCOUNTING_CONNECTION'] = $connectionValue;
        }

        parent::handle();
    }

    protected function updateEnvConnection(string $value): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $contents = file_get_contents($envPath);

        if (preg_match('/^ACCOUNTING_CONNECTION=.*/m', $contents)) {
            $contents = preg_replace(
                '/^ACCOUNTING_CONNECTION=.*/m',
                "ACCOUNTING_CONNECTION={$value}",
                $contents
            );
        } else {
            $contents = rtrim($contents, "\n") . "\nACCOUNTING_CONNECTION={$value}\n";
        }

        file_put_contents($envPath, $contents);
    }
}
