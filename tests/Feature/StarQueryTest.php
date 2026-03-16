<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Enums\AggregationType;
use Skylence\StarSchema\Enums\Range;
use Skylence\StarSchema\Services\StarSchemaRegistry;
use Skylence\StarSchema\StarQuery;
use Skylence\StarSchema\Tests\Fixtures\Order;
use Skylence\StarSchema\Tests\Fixtures\OrderFact;
use Skylence\StarSchema\TrendValue;

beforeEach(function (): void {
    DB::statement('DROP TABLE IF EXISTS orders');
    DB::statement('CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT,
        total REAL DEFAULT 0,
        quantity INTEGER DEFAULT 0,
        customer_id INTEGER DEFAULT 1,
        ordered_at DATE
    )');

    $fact = new OrderFact;
    app(StarSchemaRegistry::class)->registerFact($fact);
});

it('queries sum per month with gap filling', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 2, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 3, 'ordered_at' => '2025-01-20'],
        ['order_number' => 'O-003', 'total' => 150, 'quantity' => 1, 'ordered_at' => '2025-03-10'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 3, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perMonth()
        ->sum('total');

    expect($results)->toHaveCount(3)
        ->and($results[0])->toBeInstanceOf(TrendValue::class)
        ->and($results[0]->value)->toBe(300.0) // Jan: 100 + 200
        ->and($results[1]->value)->toBe(0)     // Feb: gap filled
        ->and($results[2]->value)->toBe(150.0); // Mar
});

it('queries sum per day', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-06-01'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 2, 'ordered_at' => '2025-06-01'],
        ['order_number' => 'O-003', 'total' => 50, 'quantity' => 1, 'ordered_at' => '2025-06-03'],
    ]);

    $from = CarbonImmutable::create(2025, 6, 1);
    $to = CarbonImmutable::create(2025, 6, 3);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perDay()
        ->sum('total');

    expect($results)->toHaveCount(3)
        ->and($results[0]->value)->toBe(300.0) // Jun 1
        ->and($results[1]->value)->toBe(0)     // Jun 2 gap
        ->and($results[2]->value)->toBe(50.0); // Jun 3
});

it('supports avg aggregation', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'ordered_at' => '2025-01-20'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 1, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perMonth()
        ->avg('total');

    expect($results[0]->value)->toBe(150.0);
});

it('supports count aggregation', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'ordered_at' => '2025-01-20'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 1, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perMonth()
        ->count('total');

    expect($results[0]->value)->toBe(2);
});

it('supports where filters', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'customer_id' => 1, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'customer_id' => 2, 'ordered_at' => '2025-01-20'],
        ['order_number' => 'O-003', 'total' => 300, 'quantity' => 1, 'customer_id' => 1, 'ordered_at' => '2025-01-25'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 1, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->where('customer_id', 1)
        ->perMonth()
        ->sum('total');

    expect($results[0]->value)->toBe(400.0); // 100 + 300
});

it('supports whereIn filters with array', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'customer_id' => 1, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'customer_id' => 2, 'ordered_at' => '2025-01-20'],
        ['order_number' => 'O-003', 'total' => 300, 'quantity' => 1, 'customer_id' => 3, 'ordered_at' => '2025-01-25'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 1, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->where('customer_id', [1, 2])
        ->perMonth()
        ->sum('total');

    expect($results[0]->value)->toBe(300.0); // 100 + 200
});

it('can disable gap filling', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-003', 'total' => 150, 'quantity' => 1, 'ordered_at' => '2025-03-10'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 3, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perMonth()
        ->withoutGapFilling()
        ->sum('total');

    // Without gap filling, only 2 rows (Jan and Mar, no Feb)
    expect($results)->toHaveCount(2);
});

it('computes scalar value without time grouping', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 2, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 3, 'ordered_at' => '2025-01-20'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 1, 31);

    $result = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->scalar('total', AggregationType::Sum);

    expect($result)->toBe(300.0);
});

it('computes growth rate against previous period', function (): void {
    Order::insert([
        // Previous period (Jan 1-15)
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-05'],
        ['order_number' => 'O-002', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-10'],
        // Current period (Jan 16-31)
        ['order_number' => 'O-003', 'total' => 150, 'quantity' => 1, 'ordered_at' => '2025-01-20'],
        ['order_number' => 'O-004', 'total' => 150, 'quantity' => 1, 'ordered_at' => '2025-01-25'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 16);
    $to = CarbonImmutable::create(2025, 1, 31);

    $result = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->growthRate('total');

    expect($result['current'])->toBe(300.0)
        ->and($result['previous'])->toBe(200.0)
        ->and($result['growth'])->toBe(50.0); // 50% growth
});

it('returns null growth when previous period is zero', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-06-15'],
    ]);

    $from = CarbonImmutable::create(2025, 6, 1);
    $to = CarbonImmutable::create(2025, 6, 30);

    $result = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->growthRate('total');

    expect($result['current'])->toBe(100.0)
        ->and($result['previous'])->toBe(0)
        ->and($result['growth'])->toBeNull();
});

it('works with named Range enum', function (): void {
    $now = CarbonImmutable::create(2025, 6, 15);

    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-06-10'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'ordered_at' => '2025-06-12'],
    ]);

    $results = StarQuery::range('test_orders', Range::Last7Days, $now)
        ->perDay()
        ->sum('total');

    // Last 7 days from Jun 15 = Jun 9 to Jun 15 = 7 days
    expect($results)->toHaveCount(7);
});

it('works with FactDefinition instance', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-15'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 1, 31);

    $results = StarQuery::fact(new OrderFact)
        ->between($from, $to)
        ->perMonth()
        ->sum('total');

    expect($results[0]->value)->toBe(100.0);
});

it('queries per quarter with gap filling', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2025-01-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'ordered_at' => '2025-07-10'],
    ]);

    $from = CarbonImmutable::create(2025, 1, 1);
    $to = CarbonImmutable::create(2025, 9, 30);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perQuarter()
        ->sum('total');

    // Q1, Q2, Q3
    expect($results)->toHaveCount(3)
        ->and($results[0]->value)->toBe(100.0) // Q1
        ->and($results[1]->value)->toBe(0)     // Q2 gap
        ->and($results[2]->value)->toBe(200.0); // Q3
});

it('queries per year with gap filling', function (): void {
    Order::insert([
        ['order_number' => 'O-001', 'total' => 100, 'quantity' => 1, 'ordered_at' => '2023-06-15'],
        ['order_number' => 'O-002', 'total' => 200, 'quantity' => 1, 'ordered_at' => '2025-06-15'],
    ]);

    $from = CarbonImmutable::create(2023, 1, 1);
    $to = CarbonImmutable::create(2025, 12, 31);

    $results = StarQuery::fact('test_orders')
        ->between($from, $to)
        ->perYear()
        ->sum('total');

    expect($results)->toHaveCount(3)
        ->and($results[0]->value)->toBe(100.0) // 2023
        ->and($results[1]->value)->toBe(0)     // 2024 gap
        ->and($results[2]->value)->toBe(200.0); // 2025
});
