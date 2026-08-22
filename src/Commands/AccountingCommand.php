<?php

namespace AloongJerr\Accounting\Commands;

use Illuminate\Console\Command;

class AccountingCommand extends Command
{
    public $signature = 'accounting';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
