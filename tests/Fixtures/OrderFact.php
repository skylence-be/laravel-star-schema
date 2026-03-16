<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Skylence\StarSchema\Contracts\FactDefinition;

final class OrderFact implements FactDefinition
{
    public function name(): string
    {
        return 'test_orders';
    }

    public function sourceModel(): string
    {
        return Order::class;
    }

    public function query(): Builder
    {
        return Order::query();
    }

    public function measures(): array
    {
        return [
            'total' => 'Order Total',
            'quantity' => 'Item Count',
        ];
    }

    public function dimensions(): array
    {
        return [];
    }

    public function degenerateDimensions(): array
    {
        return [
            'order_number' => 'Order Number',
        ];
    }

    public function dateColumn(): string
    {
        return 'ordered_at';
    }

    public function grain(): string
    {
        return 'One row per order';
    }
}
