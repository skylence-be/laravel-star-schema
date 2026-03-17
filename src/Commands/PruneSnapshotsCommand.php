<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Commands;

use Illuminate\Console\Command;
use Skylence\StarSchema\Actions\PruneSnapshots;

final class PruneSnapshotsCommand extends Command
{
    protected $signature = 'star-schema:prune';

    protected $description = 'Delete old snapshot rows based on configured retention periods';

    public function handle(PruneSnapshots $action): int
    {
        $deleted = $action->execute();

        $this->info(sprintf('Pruned %d expired snapshot rows.', $deleted));

        return self::SUCCESS;
    }
}
