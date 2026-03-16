<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Enums;

use Carbon\CarbonImmutable;

enum Range: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case Last90Days = 'last_90_days';
    case MonthToDate = 'mtd';
    case QuarterToDate = 'qtd';
    case YearToDate = 'ytd';
    case LastMonth = 'last_month';
    case LastQuarter = 'last_quarter';
    case LastYear = 'last_year';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Yesterday => 'Yesterday',
            self::Last7Days => 'Last 7 Days',
            self::Last30Days => 'Last 30 Days',
            self::Last90Days => 'Last 90 Days',
            self::MonthToDate => 'Month to Date',
            self::QuarterToDate => 'Quarter to Date',
            self::YearToDate => 'Year to Date',
            self::LastMonth => 'Last Month',
            self::LastQuarter => 'Last Quarter',
            self::LastYear => 'Last Year',
            self::All => 'All Time',
        };
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function dates(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        return match ($this) {
            self::Today => [$now->startOfDay(), $now],
            self::Yesterday => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            self::Last7Days => [$now->subDays(6)->startOfDay(), $now],
            self::Last30Days => [$now->subDays(29)->startOfDay(), $now],
            self::Last90Days => [$now->subDays(89)->startOfDay(), $now],
            self::MonthToDate => [$now->startOfMonth(), $now],
            self::QuarterToDate => [$now->startOfQuarter(), $now],
            self::YearToDate => [$now->startOfYear(), $now],
            self::LastMonth => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            self::LastQuarter => [$now->subQuarter()->startOfQuarter(), $now->subQuarter()->endOfQuarter()],
            self::LastYear => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            self::All => [CarbonImmutable::createFromDate(2000, 1, 1), $now],
        };
    }

    /**
     * Get the previous period for growth rate comparison.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function previousDates(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        [$start, $end] = $this->dates($now);
        $days = $start->diffInDays($end);

        return [$start->subDays($days + 1), $start->subDay()];
    }
}
