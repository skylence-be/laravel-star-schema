<?php

declare(strict_types=1);

namespace Skylence\StarSchema;

use Skylence\StarSchema\Commands\AggregateFactsCommand;
use Skylence\StarSchema\Commands\SeedDateDimensionCommand;
use Skylence\StarSchema\Commands\SyncDimensionsCommand;
use Skylence\StarSchema\Services\StarSchemaRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class StarSchemaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'laravel-star-schema';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('star-schema')
            ->hasMigrations([
                'create_dim_date_table',
                'create_fact_snapshots_table',
            ])
            ->hasCommands([
                SeedDateDimensionCommand::class,
                AggregateFactsCommand::class,
                SyncDimensionsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(StarSchemaRegistry::class);
    }

    public function packageBooted(): void
    {
        $registry = $this->app->make(StarSchemaRegistry::class);

        foreach (config('star-schema.facts', []) as $factClass) {
            $registry->registerFact(new $factClass);
        }

        foreach (config('star-schema.dimensions', []) as $dimensionClass) {
            $registry->registerDimension(new $dimensionClass);
        }
    }
}
