<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Commands;

use Illuminate\Console\Command;
use Skylence\StarSchema\Actions\SyncDimension;
use Skylence\StarSchema\Services\StarSchemaRegistry;

final class SyncDimensionsCommand extends Command
{
    protected $signature = 'star-schema:sync-dimensions
        {--dimension= : Specific dimension name to sync (all if omitted)}';

    protected $description = 'Sync dimension tables from their source models';

    public function handle(StarSchemaRegistry $registry, SyncDimension $action): int
    {
        $dimensionName = $this->option('dimension');
        $dimensions = $dimensionName !== null
            ? [$registry->dimension($dimensionName)]
            : $registry->dimensions();

        foreach ($dimensions as $dimension) {
            $count = $action->execute($dimension);
            $this->info("Synced {$count} rows for dimension '{$dimension->name()}'.");
        }

        return self::SUCCESS;
    }
}
