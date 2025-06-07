<?php

namespace LumoSolutions\Actionable\Console\Commands;

use Illuminate\Console\Command;
use LumoSolutions\Actionable\Actions\Console\UpdateActionDocBlocks;

class ActionsIdeHelperCommand extends Command
{
    protected $signature = 'ide-helper:actions
                            {--namespace=App\\ : The namespace to scan for actions}
                            {--dry-run : Show what would be changed without modifying files}';

    protected $description = 'Generate IDE helper doc blocks for Action classes using IsRunnable and IsDispatchable traits';

    public function handle(): int
    {
        $namespace = rtrim($this->option('namespace'), '\\').'\\';
        $dryRun = $this->option('dry-run');

        $this->info('Scanning actions in namespace: '.$namespace.($dryRun ? ' (dry-run mode)' : ' '));
        $this->newLine();

        $response = UpdateActionDocBlocks::run($namespace, $dryRun);

        if (empty($response)) {
            $this->info('No actions found or no changes needed.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->displayDryRunResults($response);
        } else {
            $this->displayUpdateResults($response);
        }

        return self::SUCCESS;
    }

    /**
     * Display the results when in dry-run mode
     */
    protected function displayDryRunResults(array $response): void
    {
        $this->comment('🔍 Dry-run mode - No files will be modified');
        $this->newLine();

        $totalChanges = 0;

        foreach ($response as $className => $changes) {
            $changeCount = count($changes);
            $totalChanges += $changeCount;

            $this->line("<fg=cyan>$className</> (<fg=yellow>$changeCount changes</>)");

            foreach ($changes as $change) {
                $type = $change['type'];
                $line = $change['line'];

                switch ($type) {
                    case '+':
                        $this->line("  <fg=green>+ $line</>");
                        break;
                    case '-':
                        $this->line("  <fg=red>- $line</>");
                        break;
                    default:
                        $this->line("  $type $line");
                }
            }

            $this->newLine();
        }

        $this->info("📊 Summary: $totalChanges changes would be made across ".count($response).' files');
        $this->comment('Run without --dry-run to apply these changes.');
    }

    /**
     * Display the results after actual updates
     */
    protected function displayUpdateResults(array $response): void
    {
        $successful = 0;

        foreach ($response as $className => $result) {
            if ($result === true) {
                $successful++;
                $this->line("<fg=green>✓</> $className");
            }
        }

        $this->newLine();
        $this->info("✨ Successfully updated $successful action files!");
    }
}
