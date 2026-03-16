<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

final class MySqlAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => "DATE({$column})",
            TimeGrain::Weekly => "DATE(DATE_SUB({$column}, INTERVAL WEEKDAY({$column}) DAY))",
            TimeGrain::Monthly => "DATE_FORMAT({$column}, '%Y-%m-01')",
            TimeGrain::Quarterly => "CONCAT(YEAR({$column}), '-', LPAD((QUARTER({$column}) - 1) * 3 + 1, 2, '0'), '-01')",
            TimeGrain::Yearly => "DATE_FORMAT({$column}, '%Y-01-01')",
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
