# Database Adapters

The package uses database-specific adapters to generate correct SQL date truncation expressions. This is what enables the same fluent API to work across MySQL, PostgreSQL, and SQLite.

## How Adapters Work

When you call `StarQuery::fact('orders')->perMonth()->sum('total')`, the query builder needs to group results by truncated dates. Each database has different syntax for this:

| Grain | MySQL | PostgreSQL | SQLite |
|---|---|---|---|
| Daily | `DATE(column)` | `DATE_TRUNC('day', column)::date` | `DATE(column)` |
| Weekly | `DATE(DATE_SUB(column, INTERVAL WEEKDAY(column) DAY))` | `DATE_TRUNC('week', column)::date` | `DATE(column, 'weekday 0', '-6 days')` |
| Monthly | `DATE_FORMAT(column, '%Y-%m-01')` | `DATE_TRUNC('month', column)::date` | `DATE(column, 'start of month')` |
| Quarterly | `CONCAT(YEAR(column), ...)` | `DATE_TRUNC('quarter', column)::date` | `DATE(column, 'start of month', ...)` |
| Yearly | `DATE_FORMAT(column, '%Y-01-01')` | `DATE_TRUNC('year', column)::date` | `DATE(column, 'start of year')` |

The adapter is selected automatically based on your database connection's driver name.

## The DateAdapter Interface

```php
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
```

## Built-in Adapters

### MySqlAdapter

Used for MySQL and MariaDB connections. Handles all five time grains using MySQL date functions.

### PgsqlAdapter

Used for PostgreSQL connections. Uses PostgreSQL's `DATE_TRUNC()` function with `::date` casting.

### SqliteAdapter

Used for SQLite connections (including in-memory databases for testing). Uses SQLite's `DATE()` function with modifiers.

## Adapter Resolution

The adapter is resolved automatically from the Eloquent connection:

```php
$adapter = StarQuery::adapterFor('mysql');  // MySqlAdapter
$adapter = StarQuery::adapterFor('pgsql');  // PgsqlAdapter
$adapter = StarQuery::adapterFor('sqlite'); // SqliteAdapter
```

Any unrecognized driver falls back to `MySqlAdapter`.

## Creating a Custom Adapter

To support a different database, implement the `DateAdapter` interface:

```php
namespace App\StarSchema\Adapters;

use Skylence\StarSchema\Adapters\DateAdapter;
use Skylence\StarSchema\Enums\TimeGrain;

class ClickHouseAdapter implements DateAdapter
{
    public function truncate(string $column, TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily     => sprintf('toDate(%s)', $column),
            TimeGrain::Weekly    => sprintf('toMonday(%s)', $column),
            TimeGrain::Monthly   => sprintf('toStartOfMonth(%s)', $column),
            TimeGrain::Quarterly => sprintf('toStartOfQuarter(%s)', $column),
            TimeGrain::Yearly    => sprintf('toStartOfYear(%s)', $column),
        };
    }

    public function carbonFormat(TimeGrain $grain): string
    {
        return match ($grain) {
            TimeGrain::Daily     => 'Y-m-d',
            TimeGrain::Weekly    => 'Y-m-d',
            TimeGrain::Monthly   => 'Y-m-01',
            TimeGrain::Quarterly => 'Y-m-01',
            TimeGrain::Yearly    => 'Y-01-01',
        };
    }
}
```

## Using a Dedicated Analytics Connection

For OLAP workloads, it's recommended to use a separate database connection to avoid impacting your transactional database:

```php
// config/database.php
'connections' => [
    'mysql' => [
        // Your main OLTP connection
    ],

    'analytics' => [
        'driver'   => 'pgsql',
        'host'     => env('ANALYTICS_DB_HOST'),
        'database' => env('ANALYTICS_DB_DATABASE'),
        // ...
    ],
],
```

```php
// config/star-schema.php
'connection' => 'analytics',
```

All star schema tables (date dimension, fact snapshots) and queries will use this connection. Your fact definitions still query the source models on their own connections — only the star schema infrastructure uses the configured connection.
