<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Skylence\StarSchema\Enums\Range;

it('returns correct date range for today', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15, 14, 30, 0);
    [$start, $end] = Range::Today->dates($now);

    expect($start->toDateTimeString())->toBe('2025-06-15 00:00:00')
        ->and($end->toDateTimeString())->toBe($now->toDateTimeString());
});

it('returns correct date range for last 7 days', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15, 14, 30, 0);
    [$start, $end] = Range::Last7Days->dates($now);

    expect($start->toDateString())->toBe('2025-06-09')
        ->and($end->toDateTimeString())->toBe($now->toDateTimeString());
});

it('returns correct date range for month to date', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15);
    [$start, $end] = Range::MonthToDate->dates($now);

    expect($start->toDateString())->toBe('2025-06-01')
        ->and($end->toDateString())->toBe('2025-06-15');
});

it('returns correct date range for quarter to date', function (): void {
    $now = CarbonImmutable::create(2025, 8, 10);
    [$start, $end] = Range::QuarterToDate->dates($now);

    expect($start->toDateString())->toBe('2025-07-01');
});

it('returns correct date range for year to date', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15);
    [$start, $end] = Range::YearToDate->dates($now);

    expect($start->toDateString())->toBe('2025-01-01');
});

it('returns correct date range for last month', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15);
    [$start, $end] = Range::LastMonth->dates($now);

    expect($start->toDateString())->toBe('2025-05-01')
        ->and($end->toDateString())->toBe('2025-05-31');
});

it('returns correct date range for last quarter', function (): void {
    $now = CarbonImmutable::create(2025, 7, 15);
    [$start, $end] = Range::LastQuarter->dates($now);

    expect($start->toDateString())->toBe('2025-04-01')
        ->and($end->toDateString())->toBe('2025-06-30');
});

it('returns correct date range for last year', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15);
    [$start, $end] = Range::LastYear->dates($now);

    expect($start->toDateString())->toBe('2024-01-01')
        ->and($end->toDateString())->toBe('2024-12-31');
});

it('computes previous period dates', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15);
    [$start, $end] = Range::Last7Days->previousDates($now);

    // Last 7 days: Jun 9 - Jun 15 (7 days span)
    // Previous: Jun 2 - Jun 8
    expect($start->toDateString())->toBe('2025-06-02')
        ->and($end->toDateString())->toBe('2025-06-08');
});

it('provides human-readable labels', function (): void {
    expect(Range::MonthToDate->label())->toBe('Month to Date')
        ->and(Range::Last30Days->label())->toBe('Last 30 Days')
        ->and(Range::All->label())->toBe('All Time');
});
