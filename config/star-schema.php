<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All star schema tables will be prefixed with this value.
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
        'start_year' => 2020,
        'end_year' => 2035,
        'fiscal_year_start_month' => 1, // January
    ],

    /*
    |--------------------------------------------------------------------------
    | Aggregation Schedule
    |--------------------------------------------------------------------------
    |
    | How often aggregate tables are refreshed.
    | Options: 'hourly', 'daily', 'weekly'
    |
    */
    'aggregation_schedule' => 'daily',

    /*
    |--------------------------------------------------------------------------
    | Registered Fact Definitions
    |--------------------------------------------------------------------------
    |
    | Array of FactDefinition class names that define your star schema.
    | These are discovered automatically if left empty.
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
    */
    'dimensions' => [],

];
