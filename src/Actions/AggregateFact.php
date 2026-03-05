<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Contracts\FactDefinition;
use Skylence\StarSchema\Enums\AggregationType;
use Skylence\StarSchema\Enums\TimeGrain;
use Skylence\StarSchema\Models\FactSnapshot;

final class AggregateFact
{
    /**
     * Aggregate a fact definition for a given time grain and period.
     *
     * @param  array<string, AggregationType>|null  $aggregations  measure => type (defaults to Sum for all)
     * @return int Number of snapshot rows created
     */
    public function execute(
        FactDefinition $fact,
        TimeGrain $grain,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?array $aggregations = null,
    ): int {
        $measures = $fact->measures();
        $aggregations ??= array_fill_keys(array_keys($measures), AggregationType::Sum);
        $dateColumn = $fact->dateColumn();

        $query = $fact->query()
            ->whereBetween($dateColumn, [$from, $to]);

        $driver = $query->getConnection()->getDriverName();

        $selectRaw = [];
        $selectRaw[] = $grain->dateTruncExpression($dateColumn, $driver).' as period_start';

        foreach ($fact->dimensions() as $fk => $dimensionClass) {
            $selectRaw[] = $fk;
        }

        foreach ($aggregations as $measure => $type) {
            $selectRaw[] = $type->expression($measure)." as agg_{$measure}";
        }

        $groupBy = [$grain->dateTruncExpression($dateColumn, $driver)];
        foreach ($fact->dimensions() as $fk => $dimensionClass) {
            $groupBy[] = $fk;
        }

        $results = $query
            ->selectRaw(implode(', ', $selectRaw))
            ->groupByRaw(implode(', ', $groupBy))
            ->get();

        $snapshot = new FactSnapshot;
        $snapshotTable = $snapshot->getTable();
        $snapshotConnection = $snapshot->getConnectionName();

        // Clear existing snapshots for this fact/grain/period
        DB::connection($snapshotConnection)->table($snapshotTable)
            ->where('fact_name', $fact->name())
            ->where('grain', $grain->value)
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
            ->delete();

        $rows = [];
        foreach ($results as $row) {
            $measuresData = [];
            foreach (array_keys($aggregations) as $measure) {
                $measuresData[$measure] = $row->{"agg_{$measure}"};
            }

            $dimensionsData = [];
            foreach ($fact->dimensions() as $fk => $dimensionClass) {
                $dimensionsData[$fk] = $row->{$fk};
            }

            $rows[] = [
                'fact_name' => $fact->name(),
                'grain' => $grain->value,
                'period_start' => $row->period_start,
                'period_end' => $row->period_start,
                'measures' => json_encode($measuresData),
                'dimensions' => json_encode($dimensionsData),
                'aggregated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::connection($snapshotConnection)->table($snapshotTable)->insert($chunk);
        }

        return count($rows);
    }
}
