<?php

namespace LumoSolutions\Actionable\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LumoSolutions\Actionable\Services\ActionDocBlockService;

class ActionsIdeHelperCommand extends Command
{
    protected $signature = 'ide-helper:actions
                            {--namespace=App\\ : The namespace to scan for actions}
                            {--dry-run : Show what would be changed without modifying files}';

    protected $description = 'Generate IDE helper doc blocks for Action classes using IsRunnable and IsDispatchable traits';

    protected ActionDocBlockService $service;

    public function __construct(ActionDocBlockService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $namespace = $this->option('namespace');
        $dryRun = $this->option('dry-run');

        $this->info("Scanning for Action classes in namespace: {$namespace} ");

        $files = $this->getPhpFiles($namespace);

        if (empty($files)) {
            $this->error("No PHP files found in namespace: {$namespace}");

            return self::FAILURE;
        }

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($files as $file) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());

            $result = $this->service->processFile($file->getPathname(), $dryRun);

            if ($result['processed']) {
                $processedCount++;

                if ($dryRun) {
                    $this->line("<info>Would update:</info> {$relativePath}");
                    $this->showDocBlockChanges($result['docBlocks']);
                } else {
                    $this->info("Updated: {$relativePath}");
                }
            } else {
                $skippedCount++;
                if ($this->output->isVerbose()) {
                    $this->line("<comment>Skipped:</comment> {$relativePath} - {$result['reason']}");
                }
            }
        }

        $this->showSummary($processedCount, $skippedCount, $errorCount, $dryRun);

        return ($errorCount > 0) ? self::FAILURE : self::SUCCESS;
    }

    private function getPhpFiles(string $namespace): array
    {
        $path = app_path(str_replace(['App\\', '\\'], ['', '/'], $namespace));

        if (! is_dir($path)) {
            return [];
        }

        return collect(File::allFiles($path))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->values()
            ->all();
    }

    private function showDocBlockChanges(array $docBlocks): void
    {
        if (empty($docBlocks)) {
            return;
        }

        $this->line('  <comment>Doc blocks to add:</comment>');
        foreach ($docBlocks as $docBlock) {
            $this->line("    <info>*</info> {$docBlock}");
        }
    }

    private function showSummary(int $processedCount, int $skippedCount, int $errorCount, bool $dryRun): void
    {
        $this->line('');
        $this->info('Summary:');

        $action = $dryRun ? 'Would be updated' : 'Updated';
        $this->line("  <info>{$action}:</info> {$processedCount} files");

        if ($skippedCount > 0 || $this->output->isVerbose()) {
            $this->line("  <comment>Skipped:</comment> {$skippedCount} files");
        }

        if ($errorCount > 0) {
            $this->line("  <error>Errors:</error> {$errorCount} files");
        }

        $this->line('');
        $status = $errorCount > 0 ? 'completed with errors' : 'completed successfully';
        $this->info("Process {$status}!");
    }
}
