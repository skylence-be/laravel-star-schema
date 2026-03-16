<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Enums;

use Skylence\StarSchema\StarQuery;

enum TimeGrain: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    /**
     * SQL date truncation expression for this grain.
     */
    public function dateTruncExpression(string $column, string $driver = 'mysql'): string
    {
        return StarQuery::adapterFor($driver)->truncate($column, $this);
    }
}
