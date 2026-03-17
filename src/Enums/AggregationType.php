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
            self::Sum => sprintf('SUM(%s)', $column),
            self::Avg => sprintf('AVG(%s)', $column),
            self::Count => sprintf('COUNT(%s)', $column),
            self::Min => sprintf('MIN(%s)', $column),
            self::Max => sprintf('MAX(%s)', $column),
        };
    }
}
