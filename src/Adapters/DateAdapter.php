<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Adapters;

use Skylence\StarSchema\Enums\TimeGrain;

interface DateAdapter
{
    /**
     * SQL expression to truncate a date column to the given grain.
     */
    public function truncate(string $column, TimeGrain $grain): string;

    /**
     * Carbon date format string matching the SQL output for this grain.
     */
    public function carbonFormat(TimeGrain $grain): string;
}
