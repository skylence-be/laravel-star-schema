<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Actions;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Models\DimDate;

final class SeedDateDimension
{
    public function execute(
        ?int $startYear = null,
        ?int $endYear = null,
        ?int $fiscalYearStartMonth = null,
    ): int {
        $startYear ??= (int) config('star-schema.date_dimension.start_year', 2020);
        $endYear ??= (int) config('star-schema.date_dimension.end_year', 2035);
        $fiscalYearStartMonth ??= (int) config('star-schema.date_dimension.fiscal_year_start_month', 1);

        $start = CarbonImmutable::createFromDate($startYear, 1, 1);
        $end = CarbonImmutable::createFromDate($endYear, 12, 31);

        $period = CarbonPeriod::create($start, $end);
        $rows = [];

        foreach ($period as $date) {
            /** @var CarbonImmutable $date */
            $date = CarbonImmutable::instance($date);

            $rows[] = [
                'date_key' => (int) $date->format('Ymd'),
                'date' => $date->toDateString(),
                'day_of_week' => $date->dayOfWeek,
                'day_of_month' => $date->day,
                'day_of_year' => $date->dayOfYear,
                'day_name' => $date->format('l'),
                'week_of_year' => $date->isoWeek(),
                'month' => $date->month,
                'month_name' => $date->format('F'),
                'quarter' => $date->quarter,
                'year' => $date->year,
                'fiscal_quarter' => $this->fiscalQuarter($date, $fiscalYearStartMonth),
                'fiscal_year' => $this->fiscalYear($date, $fiscalYearStartMonth),
                'is_weekend' => $date->isWeekend(),
                'is_holiday' => false,
            ];
        }

        $table = (new DimDate)->getTable();
        $connection = config('star-schema.connection');

        DB::connection($connection)->table($table)->truncate();

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::connection($connection)->table($table)->insert($chunk);
        }

        return count($rows);
    }

    private function fiscalQuarter(CarbonImmutable $date, int $fiscalStartMonth): int
    {
        $adjustedMonth = ($date->month - $fiscalStartMonth + 12) % 12;

        return (int) floor($adjustedMonth / 3) + 1;
    }

    private function fiscalYear(CarbonImmutable $date, int $fiscalStartMonth): int
    {
        if ($date->month >= $fiscalStartMonth) {
            return $fiscalStartMonth === 1 ? $date->year : $date->year + 1;
        }

        return $date->year;
    }
}
