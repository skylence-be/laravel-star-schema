<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Models;

use Illuminate\Database\Eloquent\Model;
use Skylence\StarSchema\Enums\TimeGrain;

final class FactSnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('star-schema.table_prefix', 'star_') . 'fact_snapshots';
    }

    public function getConnectionName(): ?string
    {
        return config('star-schema.connection');
    }

    protected function casts(): array
    {
        return [
            'grain' => TimeGrain::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'measures' => 'array',
            'dimensions' => 'array',
            'aggregated_at' => 'datetime',
        ];
    }
}
