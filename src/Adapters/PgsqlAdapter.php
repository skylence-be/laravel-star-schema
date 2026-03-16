<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

final class PgsqlAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily => "DATE_TRUNC('day', {$column})::date",
            TimeGrain::Weekly => "DATE_TRUNC('week', {$column})::date",
            TimeGrain::Monthly => "DATE_TRUNC('month', {$column})::date",
            TimeGrain::Quarterly => "DATE_TRUNC('quarter', {$column})::date",
            TimeGrain::Yearly => "DATE_TRUNC('year', {$column})::date",
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
