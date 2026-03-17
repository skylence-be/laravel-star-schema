<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Skylence\StarSchema\Actions\AggregateFact;
use Skylence\StarSchema\Enums\TimeGrain;
use Skylence\StarSchema\Services\StarSchemaRegistry;

final class AggregateFactsCommand extends Command
{
    protected $signature = 'star-schema:aggregate
        {--fact= : Specific fact name to aggregate (all if omitted)}
        {--grain= : Time grain (daily, weekly, monthly, quarterly, yearly)}
        {--from= : Start date (defaults to yesterday)}
        {--to= : End date (defaults to yesterday)}';

    protected $description = 'Aggregate registered fact definitions into snapshot rows';

    public function handle(StarSchemaRegistry $registry, AggregateFact $action): int
    {
        $grainValue = $this->option('grain')
            ?? config('star-schema.aggregation.default_grain', 'daily');
        $grain = TimeGrain::from($grainValue);

        $from = CarbonImmutable::parse($this->option('from') ?? 'yesterday');
        $to = CarbonImmutable::parse($this->option('to') ?? 'yesterday');

        $factName = $this->option('fact');
        $facts = $factName !== null
            ? [$registry->fact($factName)]
            : $registry->facts();

        $totalRows = 0;

        foreach ($facts as $fact) {
            $count = $action->execute($fact, $grain, $from, $to);
            $this->info(sprintf("Aggregated %d rows for '%s' (%s).", $count, $fact->name(), $grain->value));
            $totalRows += $count;
        }

        $this->info(sprintf('Total: %d snapshot rows created.', $totalRows));

        return self::SUCCESS;
    }
}
