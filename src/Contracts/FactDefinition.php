<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Defines a fact table in the star schema.
 *
 * A fact represents a measurable business event (order, payment, stock movement).
 */
interface FactDefinition
{
    /**
     * Unique name for this fact (e.g. 'sales_order', 'stock_movement').
     */
    public function name(): string;

    /**
     * The source Eloquent model class.
     *
     * @return class-string
     */
    public function sourceModel(): string;

    /**
     * The base query for extracting fact data.
     * Apply any default scopes/filters here.
     */
    public function query(): Builder;

    /**
     * Measures (numeric columns that can be summed/averaged).
     *
     * @return array<string, string> column => label
     */
    public function measures(): array;

    /**
     * Dimension keys (foreign keys to dimension tables).
     *
     * @return array<string, class-string<DimensionDefinition>> column => DimensionDefinition class
     */
    public function dimensions(): array;

    /**
     * Degenerate dimensions (stored directly in the fact table).
     *
     * @return array<string, string> column => label
     */
    public function degenerateDimensions(): array;

    /**
     * The date column used for time-based analysis.
     */
    public function dateColumn(): string;

    /**
     * The grain description for documentation.
     * e.g. "One row per order line item"
     */
    public function grain(): string;
}
