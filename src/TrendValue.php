<?php

declare(strict_types=1);

namespace Skylence\StarSchema;

final readonly class TrendValue
{
    public function __construct(
        public string $date,
        public float|int $value,
    ) {}
}
