# Snapshot Aggregation

The snapshot system pre-computes aggregated data from your fact tables into the `star_fact_snapshots` table. This is useful for dashboards and reports where re-aggregating raw data on every request would be too slow.

## How It Works

1. The `AggregateFact` action queries raw fact data grouped by time grain and dimension keys
2. Results are stored as JSON in the `star_fact_snapshots` table
3. Each snapshot row contains the fact name, grain, period, measures (as JSON), and dimension keys (as JSON)
4. Running aggregation for the same fact/grain/period replaces existing snapshots

### Snapshot Table Schema

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `fact_name` | string | Name from your `FactDefinition::name()` |
| `grain` | string | Time grain (`daily`, `weekly`, etc.) |
| `period_start` | date | Start of the aggregation period |
| `period_end` | date | End of the aggregation period |
| `measures` | json | `{"total_amount": 15000, "quantity": 42}` |
| `dimensions` | json | `{"customer_id": 5, "product_id": 12}` |
| `aggregated_at` | timestamp | When the snapshot was created |

## Running Aggregation

### Via Artisan Command

```bash
# Aggregate all facts for yesterday (default)
php artisan star-schema:aggregate

# Specific fact and grain
php artisan star-schema:aggregate --fact=sales_orders --grain=monthly

# Custom date range
php artisan star-schema:aggregate --from=2025-01-01 --to=2025-03-31

# Weekly grain for a specific period
php artisan star-schema:aggregate --grain=weekly --from=2025-01-01 --to=2025-01-31
```

### Via Code

```php
use Carbon\CarbonImmutable;
use Skylence\StarSchema\Actions\AggregateFact;
use Skylence\StarSchema\Enums\AggregationType;
use Skylence\StarSchema\Enums\TimeGrain;

$action = app(AggregateFact::class);
$fact = app(StarSchemaRegistry::class)->fact('sales_orders');

// Default aggregation (SUM for all measures)
$rowCount = $action->execute(
    fact: $fact,
    grain: TimeGrain::Daily,
    from: CarbonImmutable::parse('2025-01-01'),
    to: CarbonImmutable::parse('2025-01-31'),
);

// Custom aggregation types per measure
$rowCount = $action->execute(
    fact: $fact,
    grain: TimeGrain::Monthly,
    from: CarbonImmutable::parse('2025-01-01'),
    to: CarbonImmutable::parse('2025-12-31'),
    aggregations: [
        'total_amount' => AggregationType::Sum,
        'quantity'     => AggregationType::Avg,
    ],
);
```

## Scheduling

Enable automatic aggregation in `config/star-schema.php`:

```php
'schedule' => [
    'enabled' => true,
    'cron'    => '0 2 * * *',    // Every day at 2:00 AM
    'grains'  => ['daily'],       // Which grains to aggregate
    'queue'   => null,            // null = sync, or queue name
],
```

Or handle scheduling yourself in `app/Console/Kernel.php`:

```php
use Skylence\StarSchema\Commands\AggregateFactsCommand;
use Skylence\StarSchema\Commands\PruneSnapshotsCommand;

$schedule->command(AggregateFactsCommand::class, ['--grain' => 'daily'])
    ->dailyAt('02:00');

$schedule->command(AggregateFactsCommand::class, ['--grain' => 'weekly'])
    ->weeklyOn(1, '03:00'); // Monday at 3 AM

$schedule->command(AggregateFactsCommand::class, ['--grain' => 'monthly'])
    ->monthlyOn(1, '04:00'); // 1st of month at 4 AM

$schedule->command(PruneSnapshotsCommand::class)
    ->dailyAt('05:00');
```

## Retention & Pruning

Configure how long to keep snapshots per grain in `config/star-schema.php`:

```php
'retention' => [
    'daily'     => 90,    // Delete daily snapshots older than 90 days
    'weekly'    => 365,   // Delete weekly snapshots older than 1 year
    'monthly'   => null,  // Keep forever
    'quarterly' => null,  // Keep forever
    'yearly'    => null,  // Keep forever
],
```

Run pruning manually:

```bash
php artisan star-schema:prune
```

The prune command iterates over each grain, checks the retention config, and deletes snapshots where `period_start` is older than the cutoff date.

## Aggregation Types

The `AggregationType` enum supports:

| Type | SQL | Description |
|---|---|---|
| `Sum` | `SUM(column)` | Total of all values |
| `Avg` | `AVG(column)` | Average value |
| `Count` | `COUNT(column)` | Number of rows |
| `Min` | `MIN(column)` | Minimum value |
| `Max` | `MAX(column)` | Maximum value |

By default, all measures are aggregated with `SUM`. Override per-measure by passing the `$aggregations` parameter.

## Chunk Size

Large aggregations are inserted in chunks to avoid memory issues:

```php
'aggregation' => [
    'chunk_size' => 500, // Rows per batch insert
],
```

Increase for faster inserts on large datasets, decrease if you hit memory limits.
