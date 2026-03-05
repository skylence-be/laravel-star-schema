<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Actions;

use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Contracts\DimensionDefinition;
use Skylence\StarSchema\Enums\ScdType;

final class SyncDimension
{
    /**
     * Sync a dimension table from its source model.
     * Handles SCD Type 1 (overwrite) attributes by default.
     *
     * @return int Number of rows upserted
     */
    public function execute(DimensionDefinition $dimension): int
    {
        $sourceModel = new ($dimension->sourceModel());
        $connection = config('star-schema.connection');
        $attributes = $dimension->attributes();
        $scdTypes = $dimension->scdTypes();

        $columns = array_keys($attributes);
        $updateColumns = [];

        foreach ($columns as $column) {
            $scdType = ScdType::tryFrom($scdTypes[$column] ?? 1) ?? ScdType::Overwrite;

            if ($scdType === ScdType::Overwrite) {
                $updateColumns[] = $column;
            }
        }

        $query = $sourceModel->newQuery();
        $count = 0;

        $query->select(array_merge(['id'], $columns))
            ->chunkById(1000, function ($records) use ($dimension, $connection, $columns, $updateColumns, &$count): void {
                $rows = $records->map(fn ($record) => collect($record->only(array_merge(['id'], $columns)))->all())->all();

                DB::connection($connection)->table($dimension->table())
                    ->upsert($rows, ['id'], $updateColumns);

                $count += count($rows);
            });

        return $count;
    }
}
