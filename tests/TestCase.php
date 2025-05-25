<?php

namespace LumoSolutions\Actionable\Tests;

use Illuminate\Support\Facades\File;
use LumoSolutions\Actionable\ActionableProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Clean up the app/Actions directory if it exists
        if (is_dir(app_path('Actions'))) {
            File::deleteDirectory(app_path('Actions'));
        }

        // Clean up the app/DTOs directory if it exists
        if (is_dir(app_path('Dtos'))) {
            File::deleteDirectory(app_path('Dtos'));
        }

        // Clean up the stubs directory if it exists
        if (is_dir(base_path('stubs/lumosolutions/actionable'))) {
            File::deleteDirectory(base_path('stubs/lumosolutions/actionable'));
        }

        parent::tearDown();
    }

    protected function copyStubs(): void
    {
        $source = __DIR__.'\\Unit\\Console\\Configuration\\stubs';
        $destination = base_path('stubs\\lumosolutions\\actionable');

        if (! is_dir($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        File::copyDirectory($source, $destination);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionableProvider::class,
        ];
    }
}
