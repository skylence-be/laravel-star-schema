# Configuration Reference

After publishing the config with `php artisan vendor:publish --tag="star-schema-config"`, all options are available in `config/star-schema.php`.

## Table Prefix

```php
'table_prefix' => 'star_',
```

All star schema tables are prefixed with this value. The default creates:
- `star_dim_date`
- `star_fact_snapshots`

Change this to avoid collisions with existing tables or to namespace multiple star schemas.

## Database Connection

```php
'connection' => null,
```

Set to `null` to use your default Laravel database connection. Set to a connection name from `config/database.php` to use a dedicated analytics database:

```php
'connection' => 'analytics',
```

See [Database Adapters](adapters.md) for details on setting up a separate OLAP connection.

## Date Dimension

```php
'date_dimension' => [
    'start_year'              => 2020,
    'end_year'                => 2035,
    'fiscal_year_start_month' => 1,
    'week_start_day'          => 1,
    'locale'                  => null,
    'holidays'                => [],
],
```

### start_year / end_year

Range of years to populate in the date dimension table. The `star-schema:seed-dates` command generates one row per calendar day in this range.

### fiscal_year_start_month

Month number (1-12) when your fiscal year begins. Affects `fiscal_quarter` and `fiscal_year` columns:

| Setting | Fiscal Year |
|---|---|
| `1` (January) | Calendar year (Jan-Dec) |
| `4` (April) | Apr 2025 → FY2026 |
| `7` (July) | Jul 2025 → FY2026 |
| `10` (October) | Oct 2025 → FY2026 |

### week_start_day

- `0` = Sunday-based weeks (US standard)
- `1` = Monday-based weeks (ISO 8601)

### locale

Carbon locale string for localized day/month names. Set to `null` for English defaults.

```php
'locale' => 'nl', // Dutch: maandag, dinsdag, januari, februari
'locale' => 'fr', // French: lundi, mardi, janvier, février
'locale' => 'de', // German: Montag, Dienstag, Januar, Februar
```

### holidays

Static array of date strings or a callable for dynamic holiday resolution:

```php
// Static list
'holidays' => [
    '2025-01-01',
    '2025-12-25',
],

// Callable (invoked per year in the range)
'holidays' => function (int $year): array {
    return [
        "{$year}-01-01", // New Year
        "{$year}-12-25", // Christmas
    ];
},

// Class with __invoke
'holidays' => new App\Holidays\BelgianHolidays,
```

The date dimension table has an `is_holiday` boolean column set based on this config.

## Aggregation

```php
'aggregation' => [
    'default_grain' => 'daily',
    'default_type'  => 'sum',
    'chunk_size'    => 500,
],
```

### default_grain

Default time grain used by `star-schema:aggregate` when `--grain` is not specified. Options: `daily`, `weekly`, `monthly`, `quarterly`, `yearly`.

### default_type

Default aggregation type for all measures. Options: `sum`, `avg`, `count`, `min`, `max`. Can be overridden per-measure when calling `AggregateFact::execute()` directly.

### chunk_size

Number of rows per batch insert when writing snapshots. Increase for faster bulk inserts on large datasets. Decrease if you encounter memory limits.

## Retention

```php
'retention' => [
    'daily'     => 90,
    'weekly'    => 365,
    'monthly'   => null,
    'quarterly' => null,
    'yearly'    => null,
],
```

Number of days to keep snapshot rows for each grain. Set to `null` to keep indefinitely.

When `star-schema:prune` runs, it deletes snapshot rows where `period_start` is older than `now() - retention_days` for the given grain.

Recommended strategy:
- **Daily**: 90 days (recent detail)
- **Weekly**: 1 year (medium-term trends)
- **Monthly/Quarterly/Yearly**: Keep forever (long-term reporting)

## Scheduling

```php
'schedule' => [
    'enabled' => false,
    'cron'    => '0 2 * * *',
    'grains'  => ['daily'],
    'queue'   => null,
],
```

### enabled

Set to `true` to let the package automatically schedule the aggregate command. Set to `false` to handle scheduling yourself.

### cron

Cron expression for when aggregation runs. Default: every day at 2:00 AM.

### grains

Array of grain values to aggregate on schedule. Typically just `['daily']` — run weekly/monthly aggregation on separate schedules.

### queue

Queue connection/name to dispatch the aggregation job. Set to `null` for synchronous execution.

## Facts & Dimensions

```php
'facts' => [
    App\StarSchema\Facts\SalesOrderFact::class,
    App\StarSchema\Facts\PurchaseOrderFact::class,
],

'dimensions' => [
    App\StarSchema\Dimensions\CustomerDimension::class,
    App\StarSchema\Dimensions\ProductDimension::class,
],
```

Register your `FactDefinition` and `DimensionDefinition` classes here. They are instantiated and registered with the `StarSchemaRegistry` singleton when the package boots.

You can then reference facts by name in queries:

```php
StarQuery::fact('sales_orders') // Resolves via registry
```

Or pass instances directly:

```php
StarQuery::fact(new SalesOrderFact)
```

## Date Dimension Table Schema

The `star_dim_date` table created by migration:

| Column | Type | Description |
|---|---|---|
| `date_key` | integer (PK) | YYYYMMDD format (e.g. 20250315) |
| `date` | date (unique) | The calendar date |
| `day_of_week` | tinyint | 0=Sun, 1=Mon, ..., 6=Sat |
| `day_of_month` | tinyint | 1-31 |
| `day_of_year` | smallint | 1-366 |
| `day_name` | string(10) | Monday, Tuesday, ... (localized) |
| `week_of_year` | tinyint | 1-53 |
| `month` | tinyint | 1-12 |
| `month_name` | string(10) | January, February, ... (localized) |
| `quarter` | tinyint | 1-4 |
| `year` | smallint | Calendar year |
| `fiscal_quarter` | tinyint | 1-4 (based on fiscal start month) |
| `fiscal_year` | smallint | Fiscal year |
| `is_weekend` | boolean | Saturday or Sunday |
| `is_holiday` | boolean | Based on holidays config |

Indexed on: `(year, month)`, `(year, quarter)`, `fiscal_year`.
