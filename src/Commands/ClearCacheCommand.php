<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Commands;

use Daikazu\Laratone\Laratone;
use Illuminate\Console\Command;

final class ClearCacheCommand extends Command
{
    protected $signature = 'laratone:clear-cache';

    protected $description = 'Clear all Laratone cached data';

    public function handle(Laratone $laratone): int
    {
        $laratone->clearCache();

        $this->info('Laratone cache cleared successfully.');

        return self::SUCCESS;
    }
}
