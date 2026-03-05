<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Models;

use Illuminate\Database\Eloquent\Model;

final class DimDate extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'date_key';

    protected $keyType = 'int';

    protected $guarded = [];

    public function getTable(): string
    {
        return config('star-schema.table_prefix', 'star_') . 'dim_date';
    }

    public function getConnectionName(): ?string
    {
        return config('star-schema.connection');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_weekend' => 'boolean',
            'is_holiday' => 'boolean',
        ];
    }
}
