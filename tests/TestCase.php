<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Skylence\StarSchema\StarSchemaServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            StarSchemaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
