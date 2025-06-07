<?php

namespace LumoSolutions\Actionable\Actions\Console;

use Exception;
use Illuminate\Support\Facades\File;
use LumoSolutions\Actionable\Actions\Generation\GenerateDocBlocks;
use LumoSolutions\Actionable\Actions\Generation\UpdateClassDocBlock;
use LumoSolutions\Actionable\Dtos\Generation\DocBlockGenDto;
use LumoSolutions\Actionable\Dtos\Generation\DocBlockUpdateDto;
use LumoSolutions\Actionable\Support\ClassAnalyser;
use LumoSolutions\Actionable\Support\DocBlockHelper;
use LumoSolutions\Actionable\Traits\IsRunnable;

class UpdateActionDocBlocks
{
    use IsRunnable;

    public function __construct(
        protected ClassAnalyser $classAnalyser
    ) {}

    public function handle(string $namespace = '\\App', bool $dryRun = false): array
    {
        $namespace = ltrim($namespace, '\\');
        $classes = $this->findClassesInNamespace($namespace);
        $results = [];

        foreach ($classes as $className) {
            try {
                $diff = $this->processClass($className, $dryRun);

                if (! empty($diff)) {
                    $results[$className] = $diff;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return $results;
    }

    public function findClassesInNamespace(string $namespace): array
    {
        $classes = [];
        $namespacePath = str_replace('\\', '/', $namespace);

        $searchPaths = [];
        if (str_starts_with($namespace, 'App')) {
            $relativePath = str_replace('App', '', $namespacePath);
            $relativePath = ltrim($relativePath, '/');
            $searchPaths[] = app_path($relativePath);
        }

        $searchPaths[] = base_path('src/'.$namespacePath);
        $searchPaths[] = base_path($namespacePath);

        foreach ($searchPaths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = File::allFiles($path);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relativePath = $file->getRelativePathname();
                $relativePath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $classes[] = $namespace.$relativePath;
            }
        }

        return array_unique($classes);
    }

    public function processClass(string $className, bool $dryRun): array|bool
    {
        // Analyze the class
        $data = rescue(
            fn () => $this->classAnalyser->analyse($className),
            fn ($e) => null,
            false
        );

        if ($data == null) {
            return false;
        }

        $actionableTraits = collect($data->traits)
            ->filter(fn ($trait) => $trait->namespace === 'LumoSolutions\\Actionable\\Traits');

        if ($actionableTraits->isEmpty()) {
            return $dryRun ? [] : false;
        }

        $currentDocBlocks = ! empty($data->docBlock)
            ? DocBlockHelper::extract($data->docBlock)
            : [];

        $newBlocks = GenerateDocBlocks::run(
            new DocBlockGenDto(
                isRunnable: (bool) $actionableTraits->firstWhere('name', 'IsRunnable'),
                isDispatchable: (bool) $actionableTraits->firstWhere('name', 'IsDispatchable'),
                handle: collect($data->methods)->firstWhere('name', 'handle'),
                docBlocks: $currentDocBlocks,
                usings: $data->includes ?? []
            )
        );

        return UpdateClassDocBlock::run(
            new DocBlockUpdateDto(
                filePath: $data->filePath,
                className: $data->className,
                currentDocBlocks: $currentDocBlocks,
                newDocBlocks: $newBlocks
            ),
            $dryRun
        );
    }
}
