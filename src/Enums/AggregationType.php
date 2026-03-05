<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Enums;

enum AggregationType: string
{
    case Sum = 'sum';
    case Avg = 'avg';
    case Count = 'count';
    case Min = 'min';
    case Max = 'max';

    /**
     * Apply this aggregation to a query builder column.
     */
    public function expression(string $column): string
    {
        return match ($this) {
            self::Sum => "SUM({$column})",
            self::Avg => "AVG({$column})",
            self::Count => "COUNT({$column})",
            self::Min => "MIN({$column})",
            self::Max => "MAX({$column})",
        };
    }
}
