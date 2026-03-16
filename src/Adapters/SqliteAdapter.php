<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

final class SqliteAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => "DATE({$column})",
            TimeGrain::Weekly => "DATE({$column}, 'weekday 0', '-6 days')",
            TimeGrain::Monthly => "DATE({$column}, 'start of month')",
            TimeGrain::Quarterly => "DATE({$column}, 'start of month', '-' || ((CAST(STRFTIME('%m', {$column}) AS INTEGER) - 1) % 3) || ' months')",
            TimeGrain::Yearly => "DATE({$column}, 'start of year')",
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
