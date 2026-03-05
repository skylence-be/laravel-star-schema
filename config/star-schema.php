<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All star schema tables will be prefixed with this value.
    | e.g. 'star_' → star_dim_date, star_fact_snapshots
    |
    */
    'table_prefix' => 'star_',

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for star schema tables.
    | Set to null to use the default connection.
    |
    | Tip: Use a dedicated analytics connection to separate OLAP workloads
    | from your transactional (OLTP) database.
    |
    */
    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Date Dimension
    |--------------------------------------------------------------------------
    |
    | Configuration for the pre-populated date dimension table.
    |
    */
    'date_dimension' => [
        // Range of years to seed
        'start_year' => 2020,
        'end_year' => 2035,

        // Month when the fiscal year starts (1 = January, 4 = April, 7 = July, etc.)
        'fiscal_year_start_month' => 1,

        // First day of the week: 0 = Sunday, 1 = Monday (ISO)
        'week_start_day' => 1,

        // Locale for day/month names (e.g. 'en', 'nl', 'fr', 'de')
        // Uses Carbon's locale system. Set to null for English defaults.
        'locale' => null,

        // Holidays: array of date strings (Y-m-d) or a callable that returns them.
        // Example: ['2025-01-01', '2025-12-25'] or a class implementing __invoke(int $year): array
        'holidays' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Aggregation
    |--------------------------------------------------------------------------
    |
    | Settings for the fact aggregation engine.
    |
    */
    'aggregation' => [
        // Default time grain when running star-schema:aggregate without --grain
        // Options: 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'
        'default_grain' => 'daily',

        // Default aggregation type applied to all measures unless overridden
        // Options: 'sum', 'avg', 'count', 'min', 'max'
        'default_type' => 'sum',

        // Number of rows per batch insert when writing snapshots
        'chunk_size' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot Retention
    |--------------------------------------------------------------------------
    |
    | Automatically prune old snapshot rows to prevent unbounded table growth.
    | Set to null to keep all snapshots indefinitely.
    |
    */
    'retention' => [
        // Number of days to keep daily snapshots (null = forever)
        'daily' => 90,

        // Number of days to keep weekly snapshots (null = forever)
        'weekly' => 365,

        // Number of days to keep monthly/quarterly/yearly snapshots (null = forever)
        'monthly' => null,
        'quarterly' => null,
        'yearly' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Cron-style schedule for automatic aggregation.
    | Set 'enabled' to false to handle scheduling yourself.
    |
    */
    'schedule' => [
        'enabled' => false,

        // Cron expression for the aggregate command
        // Default: every day at 2:00 AM
        'cron' => '0 2 * * *',

        // Which grains to run on schedule
        'grains' => ['daily'],

        // Queue connection/name for scheduled aggregation (null = sync)
        'queue' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Registered Fact Definitions
    |--------------------------------------------------------------------------
    |
    | Array of FactDefinition class names that define your star schema.
    |
    | Example:
    |   App\StarSchema\Facts\SalesOrderFact::class,
    |   App\StarSchema\Facts\SalesOrderItemFact::class,
    |
    */
    'facts' => [],

    /*
    |--------------------------------------------------------------------------
    | Registered Dimension Definitions
    |--------------------------------------------------------------------------
    |
    | Array of DimensionDefinition class names.
    |
    | Example:
    |   App\StarSchema\Dimensions\CustomerDimension::class,
    |   App\StarSchema\Dimensions\ProductDimension::class,
    |
    */
    'dimensions' => [],

];
