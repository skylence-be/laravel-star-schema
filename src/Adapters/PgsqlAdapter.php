<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

final class PgsqlAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => sprintf("DATE_TRUNC('day', %s)::date", $column),
            TimeGrain::Weekly => sprintf("DATE_TRUNC('week', %s)::date", $column),
            TimeGrain::Monthly => sprintf("DATE_TRUNC('month', %s)::date", $column),
            TimeGrain::Quarterly => sprintf("DATE_TRUNC('quarter', %s)::date", $column),
            TimeGrain::Yearly => sprintf("DATE_TRUNC('year', %s)::date", $column),
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
