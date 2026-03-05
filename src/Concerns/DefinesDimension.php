<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Concerns;

/**
 * Trait for Eloquent models that serve as dimension table sources.
 * Provides helper methods for common dimension patterns.
 */
trait DefinesDimension
{
    /**
     * @return array<string, string> column => label
     */
    abstract public function dimensionAttributes(): array;

    /**
     * @return array<string, array<string>> parent => [children]
     */
    public function dimensionHierarchies(): array
    {
        return [];
    }
}
