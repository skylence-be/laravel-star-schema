<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Actions;

use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Enums\TimeGrain;
use Skylence\StarSchema\Models\FactSnapshot;

final class PruneSnapshots
{
    /**
     * Delete snapshot rows older than the configured retention period.
     *
     * @return int Total rows deleted
     */
    public function execute(): int
    {
        $snapshot = new FactSnapshot;
        $table = $snapshot->getTable();
        $connection = $snapshot->getConnectionName();
        $totalDeleted = 0;

        foreach (TimeGrain::cases() as $grain) {
            $days = config("star-schema.retention.{$grain->value}");

            if ($days === null) {
                continue;
            }

            $cutoff = now()->subDays((int) $days)->toDateString();

            $deleted = DB::connection($connection)->table($table)
                ->where('grain', $grain->value)
                ->where('period_start', '<', $cutoff)
                ->delete();

            $totalDeleted += $deleted;
        }

        return $totalDeleted;
    }
}
