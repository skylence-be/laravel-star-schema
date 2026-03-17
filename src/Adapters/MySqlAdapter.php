<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

final class MySqlAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => sprintf('DATE(%s)', $column),
            TimeGrain::Weekly => sprintf('DATE(DATE_SUB(%s, INTERVAL WEEKDAY(%s) DAY))', $column, $column),
            TimeGrain::Monthly => sprintf("DATE_FORMAT(%s, '%%Y-%%m-01')", $column),
            TimeGrain::Quarterly => sprintf("CONCAT(YEAR(%s), '-', LPAD((QUARTER(%s) - 1) * 3 + 1, 2, '0'), '-01')", $column, $column),
            TimeGrain::Yearly => sprintf("DATE_FORMAT(%s, '%%Y-01-01')", $column),
        };
    }

    public function carbonFormat(TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => 'Y-m-d',
            TimeGrain::Weekly => 'Y-m-d',
            TimeGrain::Monthly => 'Y-m-01',
            TimeGrain::Quarterly => 'Y-m-01',
            TimeGrain::Yearly => 'Y-01-01',
        };
    }
}
