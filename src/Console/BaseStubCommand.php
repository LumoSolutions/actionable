<?php

namespace LumoSolutions\Actionable\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Illuminate\Filesystem\join_paths;

abstract class BaseStubCommand extends Command
{
    protected function rootNamespace(): string
    {
        return 'App\\';
    }

    abstract protected function subDirectory(): string;

    public static function packageStubBasePath(): string
    {
        return join_paths(__DIR__, '..', '..', 'stubs');
    }

    public static function applicationStubBasePath(): string
    {
        return join_paths(base_path(), 'stubs', 'lumosolutions', 'actionable');
    }

    protected function resolveStubPath(string $stubName): string
    {
        $applicationStubPath = join_paths(self::applicationStubBasePath(), $stubName);

        if (File::exists($applicationStubPath)) {
            return $applicationStubPath;
        }

        // Otherwise, use the package stub
        return join_paths(self::packageStubBasePath(), $stubName);
    }

    /**
     * Handle the command execution.
     *
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $stubOptions = $this->getStubOptions();

        // Determine the stub to use
        $stubPath = $this->getStubPath($stubOptions);

        // Check if stub exists
        if (! File::exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");

            return 1;
        }

        // Get stub content
        $stub = File::get($stubPath);

        // Get class name and replace namespaces
        $className = $this->qualifyClass($name);
        $stub = $this->replaceStubVariables($stub, $className);

        // Create directories if needed
        $path = $this->getPath($className);
        $directory = dirname($path);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if the file already exists
        if (File::exists($path)) {
            $this->error($this->getTypeDescription()." {$className} already exists!");

            return 1;
        }

        // Write the file
        File::put($path, $stub);

        $this->info($this->getTypeDescription()." {$className} created successfully.");

        return 0;
    }

    /**
     * Get a description of the type being created for display in messages.
     */
    abstract protected function getTypeDescription(): string;

    /**
     * Get the stub options based on command arguments and options.
     */
    protected function getStubOptions(): array
    {
        return [];
    }

    /**
     * Get the stub path based on the provided options.
     */
    abstract protected function getStubPath(array $options): string;

    /**
     * Replace all variables in the stub file.
     */
    protected function replaceStubVariables(string $stub, string $className): string
    {
        return $this->replaceNamespace($stub, $className)->replaceClass($stub, $className);
    }

    /**
     * Get the full namespace for a given class, without the class name.
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get the destination class path.
     */
    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return app_path(str_replace('\\', DIRECTORY_SEPARATOR, $name).'.php');
    }

    /**
     * Qualify the given class name by adding the proper namespace.
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $subDir = $this->subDirectory();
        $subDir = $subDir ? $subDir.'\\' : '';

        return $rootNamespace.$subDir.$name;
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @return $this
     */
    protected function replaceNamespace(string &$stub, string $name): static
    {
        $stub = str_replace(
            ['{{ namespace }}'],
            [$this->getNamespace($name)],
            $stub
        );

        return $this;
    }

    /**
     * Replace the class name for the given stub.
     */
    protected function replaceClass(string $stub, string $name): string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace('{{ class }}', $class, $stub);
    }
}
