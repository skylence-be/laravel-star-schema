<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Contracts\FactDefinition;
use Skylence\StarSchema\Enums\AggregationType;
use Skylence\StarSchema\Enums\TimeGrain;
use Skylence\StarSchema\StarQuery;

final class QueryFact
{
    /**
     * Build an aggregated query against a fact definition.
     *
     * @param  array<string, AggregationType>  $measures  measure => aggregation type
     * @param  array<string, mixed>  $dimensionFilters  dimension_fk => value or array of values
     * @param  array<string>  $groupByDimensions  dimension FK columns to group by
     */
    public function execute(
        FactDefinition $fact,
        array $measures,
        TimeGrain $grain,
        ?string $from = null,
        ?string $to = null,
        array $dimensionFilters = [],
        array $groupByDimensions = [],
    ): Builder {
        $dateColumn = $fact->dateColumn();
        $query = $fact->query();
        $adapter = StarQuery::adapterFor($query->getConnection()->getDriverName());

        if ($from !== null) {
            $query->where($dateColumn, '>=', $from);
        }

        if ($to !== null) {
            $query->where($dateColumn, '<=', $to);
        }

        foreach ($dimensionFilters as $fk => $value) {
            if (is_array($value)) {
                $query->whereIn($fk, $value);
            } else {
                $query->where($fk, $value);
            }
        }

        $selects = [];
        $groups = [];

        // Time grain grouping
        $truncExpr = $adapter->truncate($dateColumn, $grain);
        $selects[] = DB::raw($truncExpr.' as period');
        $groups[] = DB::raw($truncExpr);

        // Dimension grouping
        foreach ($groupByDimensions as $dimFk) {
            $selects[] = $dimFk;
            $groups[] = $dimFk;
        }

        // Measure aggregations
        foreach ($measures as $measure => $aggregation) {
            $selects[] = DB::raw($aggregation->expression($measure).(' as '.$measure));
        }

        return $query->select($selects)->groupBy($groups);
    }
}
