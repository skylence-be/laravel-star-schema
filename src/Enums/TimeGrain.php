<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Enums;

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
        return match ($driver) {
            'pgsql' => $this->pgsqlTrunc($column),
            default => $this->mysqlTrunc($column),
        };
    }

    private function mysqlTrunc(string $column): string
    {
        return match ($this) {
            self::Daily => "DATE({$column})",
            self::Weekly => "DATE(DATE_SUB({$column}, INTERVAL WEEKDAY({$column}) DAY))",
            self::Monthly => "DATE_FORMAT({$column}, '%Y-%m-01')",
            self::Quarterly => "CONCAT(YEAR({$column}), '-', LPAD((QUARTER({$column}) - 1) * 3 + 1, 2, '0'), '-01')",
            self::Yearly => "DATE_FORMAT({$column}, '%Y-01-01')",
        };
    }

    private function pgsqlTrunc(string $column): string
    {
        return match ($this) {
            self::Daily => "DATE_TRUNC('day', {$column})",
            self::Weekly => "DATE_TRUNC('week', {$column})",
            self::Monthly => "DATE_TRUNC('month', {$column})",
            self::Quarterly => "DATE_TRUNC('quarter', {$column})",
            self::Yearly => "DATE_TRUNC('year', {$column})",
        };
    }
}
