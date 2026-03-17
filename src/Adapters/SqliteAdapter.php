<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

final class SqliteAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => sprintf('DATE(%s)', $column),
            TimeGrain::Weekly => sprintf("DATE(%s, 'weekday 0', '-6 days')", $column),
            TimeGrain::Monthly => sprintf("DATE(%s, 'start of month')", $column),
            TimeGrain::Quarterly => sprintf("DATE(%s, 'start of month', '-' || ((CAST(STRFTIME('%%m', %s) AS INTEGER) - 1) %% 3) || ' months')", $column, $column),
            TimeGrain::Yearly => sprintf("DATE(%s, 'start of year')", $column),
        };
    }

    public function carbonFormat(TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => 'Y-m-d',
            TimeGrain::Weekly => 'Y-m-d',
            TimeGrain::Monthly => 'Y-m-d',
            TimeGrain::Quarterly => 'Y-m-d',
            TimeGrain::Yearly => 'Y-m-d',
        };
    }
}
