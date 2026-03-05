<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Services;

use InvalidArgumentException;
use Skylence\StarSchema\Contracts\DimensionDefinition;
use Skylence\StarSchema\Contracts\FactDefinition;

final class StarSchemaRegistry
{
    /** @var array<string, FactDefinition> */
    private array $facts = [];

    /** @var array<string, DimensionDefinition> */
    private array $dimensions = [];

    public function registerFact(FactDefinition $fact): void
    {
        $this->facts[$fact->name()] = $fact;
    }

    public function registerDimension(DimensionDefinition $dimension): void
    {
        $this->dimensions[$dimension->name()] = $dimension;
    }

    public function fact(string $name): FactDefinition
    {
        return $this->facts[$name] ?? throw new InvalidArgumentException("Fact '{$name}' is not registered.");
    }

    public function dimension(string $name): DimensionDefinition
    {
        return $this->dimensions[$name] ?? throw new InvalidArgumentException("Dimension '{$name}' is not registered.");
    }

    /** @return array<string, FactDefinition> */
    public function facts(): array
    {
        return $this->facts;
    }

    /** @return array<string, DimensionDefinition> */
    public function dimensions(): array
    {
        return $this->dimensions;
    }
}
