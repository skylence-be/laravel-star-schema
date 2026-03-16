<?php

declare(strict_types=1);

use Skylence\StarSchema\Adapters\MySqlAdapter;
use Skylence\StarSchema\Adapters\PgsqlAdapter;
use Skylence\StarSchema\Adapters\SqliteAdapter;
use Skylence\StarSchema\Enums\TimeGrain;

it('generates mysql date truncation expressions', function (TimeGrain $grain, string $expected): void {
    $adapter = new MySqlAdapter;

    expect($adapter->truncate('ordered_at', $grain))->toBe($expected);
})->with([
    [TimeGrain::Daily, 'DATE(ordered_at)'],
    [TimeGrain::Weekly, 'DATE(DATE_SUB(ordered_at, INTERVAL WEEKDAY(ordered_at) DAY))'],
    [TimeGrain::Monthly, "DATE_FORMAT(ordered_at, '%Y-%m-01')"],
    [TimeGrain::Quarterly, "CONCAT(YEAR(ordered_at), '-', LPAD((QUARTER(ordered_at) - 1) * 3 + 1, 2, '0'), '-01')"],
    [TimeGrain::Yearly, "DATE_FORMAT(ordered_at, '%Y-01-01')"],
]);

it('generates postgresql date truncation expressions', function (TimeGrain $grain, string $expected): void {
    $adapter = new PgsqlAdapter;

    expect($adapter->truncate('ordered_at', $grain))->toBe($expected);
})->with([
    [TimeGrain::Daily, "DATE_TRUNC('day', ordered_at)::date"],
    [TimeGrain::Weekly, "DATE_TRUNC('week', ordered_at)::date"],
    [TimeGrain::Monthly, "DATE_TRUNC('month', ordered_at)::date"],
    [TimeGrain::Quarterly, "DATE_TRUNC('quarter', ordered_at)::date"],
    [TimeGrain::Yearly, "DATE_TRUNC('year', ordered_at)::date"],
]);

it('generates sqlite date truncation expressions', function (TimeGrain $grain, string $expected): void {
    $adapter = new SqliteAdapter;

    expect($adapter->truncate('ordered_at', $grain))->toBe($expected);
})->with([
    [TimeGrain::Daily, 'DATE(ordered_at)'],
    [TimeGrain::Weekly, "DATE(ordered_at, 'weekday 0', '-6 days')"],
    [TimeGrain::Monthly, "DATE(ordered_at, 'start of month')"],
    [TimeGrain::Quarterly, "DATE(ordered_at, 'start of month', '-' || ((CAST(STRFTIME('%m', ordered_at) AS INTEGER) - 1) % 3) || ' months')"],
    [TimeGrain::Yearly, "DATE(ordered_at, 'start of year')"],
]);

it('delegates from TimeGrain to adapters', function (): void {
    expect(TimeGrain::Monthly->dateTruncExpression('col', 'mysql'))
        ->toBe("DATE_FORMAT(col, '%Y-%m-01')")
        ->and(TimeGrain::Monthly->dateTruncExpression('col', 'pgsql'))
        ->toBe("DATE_TRUNC('month', col)::date")
        ->and(TimeGrain::Monthly->dateTruncExpression('col', 'sqlite'))
        ->toBe("DATE(col, 'start of month')");
});
