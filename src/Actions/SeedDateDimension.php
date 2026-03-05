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
        $weekStartDay = (int) config('star-schema.date_dimension.week_start_day', 1);
        $locale = config('star-schema.date_dimension.locale');
        $holidays = $this->resolveHolidays($startYear, $endYear);

        $start = CarbonImmutable::createFromDate($startYear, 1, 1);
        $end = CarbonImmutable::createFromDate($endYear, 12, 31);

        if ($locale !== null) {
            $start = $start->locale($locale);
        }

        $period = CarbonPeriod::create($start, $end);
        $rows = [];

        foreach ($period as $date) {
            /** @var CarbonImmutable $date */
            $date = CarbonImmutable::instance($date);

            if ($locale !== null) {
                $date = $date->locale($locale);
            }

            $dateString = $date->toDateString();

            $rows[] = [
                'date_key' => (int) $date->format('Ymd'),
                'date' => $dateString,
                'day_of_week' => $date->dayOfWeek,
                'day_of_month' => $date->day,
                'day_of_year' => $date->dayOfYear,
                'day_name' => $date->isoFormat('dddd'),
                'week_of_year' => $weekStartDay === 0
                    ? (int) $date->format('W') // Sunday-based
                    : $date->isoWeek(),        // Monday-based (ISO)
                'month' => $date->month,
                'month_name' => $date->isoFormat('MMMM'),
                'quarter' => $date->quarter,
                'year' => $date->year,
                'fiscal_quarter' => $this->fiscalQuarter($date, $fiscalYearStartMonth),
                'fiscal_year' => $this->fiscalYear($date, $fiscalYearStartMonth),
                'is_weekend' => $date->isWeekend(),
                'is_holiday' => isset($holidays[$dateString]),
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

    /**
     * Resolve holidays from config — supports arrays and callables.
     *
     * @return array<string, true> date string => true (for fast lookup)
     */
    private function resolveHolidays(int $startYear, int $endYear): array
    {
        $config = config('star-schema.date_dimension.holidays', []);

        if ($config === [] || $config === null) {
            return [];
        }

        // Callable: invoked per year, must return array of date strings
        if (is_callable($config)) {
            $dates = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                foreach ($config($year) as $date) {
                    $dates[$date] = true;
                }
            }

            return $dates;
        }

        // Plain array of date strings
        return array_fill_keys($config, true);
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
