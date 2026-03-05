<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Defines a dimension table in the star schema.
 *
 * A dimension provides descriptive context for facts (who, what, where).
 */
interface DimensionDefinition
{
    /**
     * Unique name for this dimension (e.g. 'product', 'customer').
     */
    public function name(): string;

    /**
     * The source Eloquent model class.
     *
     * @return class-string
     */
    public function sourceModel(): string;

    /**
     * The source table name (for direct DB queries).
     */
    public function table(): string;

    /**
     * Attributes available for grouping/filtering.
     *
     * @return array<string, string> column => label
     */
    public function attributes(): array;

    /**
     * Hierarchical attributes (e.g. category → subcategory).
     *
     * @return array<string, array<string>> parent_column => [child_columns]
     */
    public function hierarchies(): array;

    /**
     * SCD (Slowly Changing Dimension) type for each attribute.
     *
     * @return array<string, int> column => SCD type (0, 1, 2)
     */
    public function scdTypes(): array;
}
