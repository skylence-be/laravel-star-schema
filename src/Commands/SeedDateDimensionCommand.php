<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Commands;

use Illuminate\Console\Command;
use Skylence\StarSchema\Actions\SeedDateDimension;

final class SeedDateDimensionCommand extends Command
{
    protected $signature = 'star-schema:seed-dates
        {--start-year= : Override start year}
        {--end-year= : Override end year}
        {--fiscal-start= : Fiscal year start month (1-12)}';

    protected $description = 'Seed the date dimension table';

    public function handle(SeedDateDimension $action): int
    {
        $startYear = $this->option('start-year') ? (int) $this->option('start-year') : null;
        $endYear = $this->option('end-year') ? (int) $this->option('end-year') : null;
        $fiscalStart = $this->option('fiscal-start') ? (int) $this->option('fiscal-start') : null;

        $count = $action->execute($startYear, $endYear, $fiscalStart);

        $this->info(sprintf('Seeded %d date dimension rows.', $count));

        return self::SUCCESS;
    }
}
