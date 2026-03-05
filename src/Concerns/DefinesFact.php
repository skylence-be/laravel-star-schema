<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Concerns;

/**
 * Trait for Eloquent models that serve as fact table sources.
 * Provides helper methods for common fact query patterns.
 */
trait DefinesFact
{
    public function factDateColumn(): string
    {
        return 'created_at';
    }

    /**
     * @return array<string, string> column => label
     */
    abstract public function factMeasures(): array;
}
