<?php

namespace LumoSolutions\Actionable\Tests;

use Illuminate\Support\Facades\File;
use LumoSolutions\Actionable\ActionableProvider;
use Orchestra\Testbench\TestCase as Orchestra;

use function Illuminate\Filesystem\join_paths;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $directories = [
            app_path('Actions'),
            app_path('Dtos'),
            join_paths(base_path(), 'stubs', 'lumosolutions', 'actionable'),
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                File::deleteDirectory($dir);
                clearstatcache();
                if (is_dir($dir)) {
                    dump("Failed to delete directory: {$dir}");
                    throw new \RuntimeException("Failed to delete directory: {$dir}");
                }
            }
        }

        parent::tearDown();
    }

    protected function copyStubs(): void
    {
        $source = join_paths(__DIR__, 'Feature', 'Console', 'Configuration', 'stubs');
        $destination = join_paths(base_path(), 'stubs', 'lumosolutions', 'actionable');

        if (! is_dir($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        File::copyDirectory($source, $destination);

        if (! File::exists(join_paths($destination, 'action.stub'))) {
            dump("Failed to copy stubs to {$destination}");
            throw new \RuntimeException("Failed to copy stubs to {$destination}");
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionableProvider::class,
        ];
    }
}
